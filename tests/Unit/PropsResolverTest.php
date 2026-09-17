<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Tests\Unit;

use Crenspire\Yii2Inertia\Props\AlwaysProp;
use Crenspire\Yii2Inertia\Props\DeferProp;
use Crenspire\Yii2Inertia\Props\MergeProp;
use Crenspire\Yii2Inertia\Props\OnceProp;
use Crenspire\Yii2Inertia\Props\OptionalProp;
use Crenspire\Yii2Inertia\Props\PropertyContext;
use Crenspire\Yii2Inertia\Props\ProvidesInertiaProperties;
use Crenspire\Yii2Inertia\Props\ProvidesInertiaProperty;
use Crenspire\Yii2Inertia\Props\RenderContext;
use Crenspire\Yii2Inertia\Props\ScrollMetadata;
use Crenspire\Yii2Inertia\Props\ScrollProp;
use Crenspire\Yii2Inertia\PropsResolver;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yii;
use yii\base\Model;
use yii\data\ArrayDataProvider;
use yii\log\Logger;
use yii\web\Request;

class PropsResolverTest extends TestCase
{
    /**
     * @param array<string, string> $headers
     * @param array<int|string, mixed> $props
     * @param array<int|string, mixed> $shared
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function resolve(array $props, array $headers = [], array $shared = [], string $component = 'Page'): array
    {
        $request = new Request();
        foreach ($headers as $name => $value) {
            $request->headers->set($name, $value);
        }

        return (new PropsResolver($request, $component))->resolve($shared, $props);
    }

    /**
     * @return array<string, string>
     */
    private function partial(string $only = '', string $except = '', string $component = 'Page'): array
    {
        return array_filter([
            'X-Inertia' => 'true',
            'X-Inertia-Partial-Component' => $component,
            'X-Inertia-Partial-Data' => $only,
            'X-Inertia-Partial-Except' => $except,
        ]);
    }

    private static function optionalProp(callable $callback): OptionalProp
    {
        return new OptionalProp($callback);
    }

    private static function deferProp(callable $callback, ?string $group = null, bool $rescue = false): DeferProp
    {
        return new DeferProp($callback, $group, $rescue);
    }

    private static function alwaysProp(mixed $value): AlwaysProp
    {
        return new AlwaysProp($value);
    }

    private static function mergeProp(mixed $value): MergeProp
    {
        return new MergeProp($value);
    }

    private static function deepMergeProp(mixed $value): MergeProp
    {
        return (new MergeProp($value))->deepMerge();
    }

    private static function onceProp(callable $callback): OnceProp
    {
        return new OnceProp($callback);
    }

    private static function scrollProp(mixed $value, string $wrapper = 'data', mixed $metadata = null): ScrollProp
    {
        return new ScrollProp($value, $wrapper, $metadata);
    }

    private function failing(string $name): \Closure
    {
        return fn () => $this->fail("\"{$name}\" must not be evaluated");
    }

    public function testInitialLoadResolvesPropsAndAnnouncesDeferredProps(): void
    {
        [$props, $metadata] = $this->resolve([
            'plain' => 'value',
            'closure' => fn () => 'from closure',
            'optional' => self::optionalProp($this->failing('optional')),
            'comments' => self::deferProp($this->failing('comments')),
            'sidebar' => self::deferProp($this->failing('sidebar'), 'aside'),
            'always' => self::alwaysProp(fn () => 'always'),
        ]);

        $this->assertSame(['plain' => 'value', 'closure' => 'from closure', 'always' => 'always'], $props);
        $this->assertSame(['deferredProps' => ['default' => ['comments'], 'aside' => ['sidebar']]], $metadata);
    }

    public function testSharedPropKeysAreExposed(): void
    {
        [$props, $metadata] = $this->resolve(['page' => 1, 'app' => 'overridden'], [], ['app' => 'shared', 'auth.user' => fn () => ['id' => 1]]);

        $this->assertEquals(['app' => 'overridden', 'auth' => ['user' => ['id' => 1]], 'page' => 1], $props);
        $this->assertSame(['app', 'auth'], $metadata['sharedProps']);

        $request = new Request();
        [, $metadata] = (new PropsResolver($request, 'Page', false))->resolve(['app' => 1], []);
        $this->assertArrayNotHasKey('sharedProps', $metadata);
    }

    public function testPartialReloadOnlyResolvesRequestedPaths(): void
    {
        [$props] = $this->resolve([
            'users' => fn () => [1, 2],
            'stats' => $this->failing('stats'),
            'optional' => self::optionalProp(fn () => 'optional'),
            'deferred' => self::deferProp(fn () => 'deferred'),
            'errors' => self::alwaysProp(fn () => []),
        ], $this->partial('users,optional,deferred'), ['auth' => $this->failing('auth')]);

        $this->assertSame(['users' => [1, 2], 'optional' => 'optional', 'deferred' => 'deferred', 'errors' => []], $props);
    }

    public function testPartialReloadWithNestedPaths(): void
    {
        $props = [
            'auth' => [
                'user' => fn () => ['id' => 1, 'name' => 'Ann'],
                'permissions' => $this->failing('permissions'),
                'token' => $this->failing('token'),
            ],
            'other' => $this->failing('other'),
        ];

        [$resolved] = $this->resolve($props, $this->partial('auth.user'));
        $this->assertSame(['auth' => ['user' => ['id' => 1, 'name' => 'Ann']]], $resolved);

        $props['auth']['permissions'] = fn () => ['edit'];
        [$resolved] = $this->resolve($props, $this->partial('auth', 'auth.token'));
        $this->assertSame(['auth' => ['user' => ['id' => 1, 'name' => 'Ann'], 'permissions' => ['edit']]], $resolved);
    }

    public function testChildrenOfResolvedValuesBypassPartialFilters(): void
    {
        [$props] = $this->resolve(['auth' => fn () => ['user' => 1, 'token' => 2]], $this->partial('auth.user'));

        $this->assertSame(['auth' => ['user' => 1, 'token' => 2]], $props);
    }

    public function testPartialReloadForAnotherComponentIsAFullResponse(): void
    {
        [$props, $metadata] = $this->resolve(['a' => 1, 'b' => self::deferProp(fn () => 2)], $this->partial('a', '', 'Other'));

        $this->assertSame(['a' => 1], $props);
        $this->assertSame(['default' => ['b']], $metadata['deferredProps']);
    }

    public function testNestedPropTypes(): void
    {
        [$props, $metadata] = $this->resolve([
            'auth' => [
                'user' => 'Ann',
                'permissions' => self::deferProp($this->failing('permissions')),
                'teams' => fn () => self::deferProp($this->failing('teams'), 'teams'),
            ],
        ]);

        $this->assertSame(['auth' => ['user' => 'Ann']], $props);
        $this->assertSame(['default' => ['auth.permissions'], 'teams' => ['auth.teams']], $metadata['deferredProps']);

        [$props] = $this->resolve(['auth' => ['user' => 'Ann', 'permissions' => self::deferProp(fn () => ['edit'])]], $this->partial('auth.permissions'));
        $this->assertSame(['auth' => ['permissions' => ['edit']]], $props);
    }

    public function testMergeMetadata(): void
    {
        $props = [
            'posts' => self::mergeProp(fn () => [['id' => 1]])->matchOn('id'),
            'notifications' => self::mergeProp([])->prepend(),
            'settings' => self::deepMergeProp(['a' => ['b' => 1]]),
            'feed' => self::mergeProp(['data' => [], 'pinned' => []])->append('data', 'uuid')->prepend('pinned'),
            'comments' => self::deferProp(fn () => [])->merge(),
        ];

        [, $metadata] = $this->resolve($props);
        $this->assertSame(['posts', 'feed.data', 'comments'], $metadata['mergeProps']);
        $this->assertSame(['notifications', 'feed.pinned'], $metadata['prependProps']);
        $this->assertSame(['settings'], $metadata['deepMergeProps']);
        $this->assertSame(['posts.id', 'feed.data.uuid'], $metadata['matchPropsOn']);

        [, $metadata] = $this->resolve($props, ['X-Inertia-Reset' => 'posts,feed']);
        $this->assertSame(['comments'], $metadata['mergeProps']);

        [, $metadata] = $this->resolve($props, $this->partial('comments'));
        $this->assertSame(['comments'], $metadata['mergeProps']);
        $this->assertArrayNotHasKey('prependProps', $metadata);
    }

    public function testOnceProps(): void
    {
        $props = [
            'plans' => self::onceProp(fn () => ['basic']),
            'countries' => self::onceProp(fn () => ['NL'])->as('geo')->until(60),
        ];

        [$resolved, $metadata] = $this->resolve($props);
        $this->assertSame(['plans' => ['basic'], 'countries' => ['NL']], $resolved);
        $this->assertSame(['prop' => 'plans', 'expiresAt' => null], $metadata['onceProps']['plans']);
        $this->assertSame('countries', $metadata['onceProps']['geo']['prop']);
        $this->assertEqualsWithDelta((time() + 60) * 1000, $metadata['onceProps']['geo']['expiresAt'], 2000);

        $alreadyLoaded = ['X-Inertia' => 'true', 'X-Inertia-Except-Once-Props' => 'plans,geo'];
        [$resolved, $metadata] = $this->resolve([
            'plans' => self::onceProp($this->failing('plans')),
            'countries' => self::onceProp(fn () => ['BE'])->as('geo')->fresh(),
        ], $alreadyLoaded);
        $this->assertSame(['countries' => ['BE']], $resolved);
        $this->assertSame(['plans', 'geo'], array_keys($metadata['onceProps']));

        // A full page load (not an Inertia visit) always sends the values.
        [$resolved] = $this->resolve($props, ['X-Inertia-Except-Once-Props' => 'plans']);
        $this->assertArrayHasKey('plans', $resolved);

        [, $metadata] = $this->resolve(['stats' => self::deferProp(fn () => 1)->once()], array_merge($alreadyLoaded, ['X-Inertia-Except-Once-Props' => 'stats']));
        $this->assertArrayNotHasKey('deferredProps', $metadata);
    }

    public function testRescuedDeferredProps(): void
    {
        Yii::setLogger(new Logger());
        $props = [
            'permissions' => self::deferProp(fn () => throw new RuntimeException('down'), 'default', true),
            'stats' => self::deferProp(fn () => 1),
        ];

        [$resolved, $metadata] = $this->resolve($props, $this->partial('permissions,stats'));

        $this->assertSame(['stats' => 1], $resolved);
        $this->assertSame(['permissions'], $metadata['rescuedProps']);

        $this->expectException(RuntimeException::class);
        $this->resolve(['stats' => self::deferProp(fn () => throw new RuntimeException('down'))], $this->partial('stats'));
    }

    public function testScrollPropsFromDataProvider(): void
    {
        $provider = fn () => new ArrayDataProvider([
            'allModels' => [['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4], ['id' => 5]],
            'pagination' => ['pageSize' => 2, 'params' => ['page' => 2]],
            'sort' => false,
        ]);

        [$props, $metadata] = $this->resolve(['posts' => self::scrollProp($provider)]);
        $this->assertSame(['posts' => ['data' => [['id' => 3], ['id' => 4]]]], $props);
        $this->assertSame(['posts.data'], $metadata['mergeProps']);
        $this->assertSame(
            ['posts' => ['pageName' => 'page', 'previousPage' => 1, 'nextPage' => 3, 'currentPage' => 2, 'reset' => false]],
            $metadata['scrollProps'],
        );

        [, $metadata] = $this->resolve(['posts' => self::scrollProp($provider)], $this->partial('posts') + ['X-Inertia-Infinite-Scroll-Merge-Intent' => 'prepend']);
        $this->assertSame(['posts.data'], $metadata['prependProps']);

        [, $metadata] = $this->resolve(['posts' => self::scrollProp($provider)], $this->partial('posts') + ['X-Inertia-Reset' => 'posts']);
        $this->assertArrayNotHasKey('mergeProps', $metadata);
        $this->assertTrue($metadata['scrollProps']['posts']['reset']);
    }

    public function testScrollPropsWithCustomMetadata(): void
    {
        [$props, $metadata] = $this->resolve([
            'items' => self::scrollProp(['items' => [1, 2]], 'items', fn (array $value) => new ScrollMetadata('cursor', null, 'abc', 'xyz')),
        ]);

        $this->assertSame(['items' => ['items' => [1, 2]]], $props);
        $this->assertSame(['items.items'], $metadata['mergeProps']);
        $this->assertSame('abc', $metadata['scrollProps']['items']['nextPage']);

        $this->expectException(InvalidArgumentException::class);
        $this->resolve(['items' => self::scrollProp([1, 2])]);
    }

    public function testPropertyProviders(): void
    {
        $provider = new class () implements ProvidesInertiaProperties {
            public function toInertiaProperties(RenderContext $context): iterable
            {
                return ['component' => $context->component, 'lazy' => fn () => 'lazy'];
            }
        };
        $property = new class () implements ProvidesInertiaProperty {
            public function toInertiaProperty(PropertyContext $prop): mixed
            {
                return $prop->key . ':' . count($prop->props);
            }
        };

        [$props] = $this->resolve([$provider, 'nested' => ['value' => $property, 'sibling' => 1]], [], [], 'Users/Index');

        $this->assertSame(['component' => 'Users/Index', 'lazy' => 'lazy', 'nested' => ['value' => 'nested.value:2', 'sibling' => 1]], $props);
    }

    public function testValuesAreConvertedToArrays(): void
    {
        $model = new class () extends Model {
            public string $name = 'Ann';
            public string $passwordHash = 'secret';

            public function fields(): array
            {
                return ['name', 'initial' => fn () => $this->name[0]];
            }
        };
        $json = new class () implements \JsonSerializable {
            public function jsonSerialize(): array
            {
                return ['nested' => fn () => 'closure'];
            }
        };

        [$props] = $this->resolve(['model' => $model, 'models' => [$model], 'json' => $json]);

        $this->assertSame([
            'model' => ['name' => 'Ann', 'initial' => 'A'],
            'models' => [['name' => 'Ann', 'initial' => 'A']],
            'json' => ['nested' => 'closure'],
        ], $props);
    }

    public function testDotPropsMergeIntoExistingValues(): void
    {
        [$props] = $this->resolve([
            'auth' => fn () => ['user' => ['id' => 1]],
            'auth.user.name' => 'Ann',
            'meta.title' => fn () => 'Home',
        ]);

        $this->assertSame(['auth' => ['user' => ['id' => 1, 'name' => 'Ann']], 'meta' => ['title' => 'Home']], $props);
    }

    public function testEmptyPartialHeadersReturnAllProps(): void
    {
        [$props] = $this->resolve(['a' => 1, 'b' => 2], ['X-Inertia-Partial-Component' => 'Page', 'X-Inertia-Partial-Data' => ' , ']);

        $this->assertSame(['a' => 1, 'b' => 2], $props);
    }
}
