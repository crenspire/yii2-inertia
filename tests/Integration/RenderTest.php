<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Tests\Integration;

use Crenspire\Yii2Inertia\Inertia;
use Crenspire\Yii2Inertia\Manager;
use Crenspire\Yii2Inertia\Props\ProvidesInertiaProperties;
use Crenspire\Yii2Inertia\Props\RenderContext;
use Crenspire\Yii2Inertia\Ssr\HttpGateway;
use Crenspire\Yii2Inertia\Ssr\SsrResponse;
use Crenspire\Yii2Inertia\Tests\Support\FakeGateway;
use Crenspire\Yii2Inertia\Tests\Support\PageComponent;
use Crenspire\Yii2Inertia\Tests\TestCase;
use InvalidArgumentException;
use JsonException;
use Yii;
use yii\base\DynamicModel;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\web\Request;
use yii\web\Response;

class RenderTest extends TestCase
{
    public function testInertiaRequestReturnsPageObjectAsJson(): void
    {
        $this->createApp('/users?page=2&sort=name', 'GET', $this->inertiaHeaders());
        Inertia::version('v1');

        $response = Inertia::render('Users/Index', ['users' => fn () => [['id' => 1]]]);

        $this->assertSame(Response::FORMAT_RAW, $response->format);
        $this->assertSame('true', $response->headers->get('X-Inertia'));
        $this->assertSame('application/json; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertSame('X-Inertia', $response->headers->get('Vary'));
        $this->assertSame(
            '{"component":"Users\/Index","props":{"errors":{},"users":[{"id":1}]},"url":"\/users?page=2&sort=name",'
            . '"version":"v1","sharedProps":["errors"]}',
            $response->data,
        );
    }

    public function testInitialVisitEmbedsPageInScriptElement(): void
    {
        $this->createApp('/search?q=a&b=1');
        $props = ['html' => '</script><script>alert("x")</script>', 'quote' => "it's & <b>", 'unicode' => 'héllo 👋'];

        $response = Inertia::render('Search', $props, ['title' => 'Search <page>']);

        $this->assertSame(Response::FORMAT_HTML, $response->format);
        $this->assertStringContainsString('<title>Search &lt;page&gt;</title>', $response->data);
        $this->assertSame(1, substr_count($response->data, '</script>'), 'user data must not close the script element');
        $page = $this->htmlPage($response);
        $this->assertSame('Search', $page['component']);
        $this->assertSame('/search?q=a&b=1', $page['url']);
        $this->assertEquals($props + ['errors' => []], $page['props']);
    }

    public function testInitialVisitAndInertiaVisitProduceTheSamePage(): void
    {
        $model = new class () extends Model {
            public int $id = 7;
            public string $passwordHash = 'secret';

            public function fields(): array
            {
                return ['id'];
            }
        };

        $this->createApp('/profile');
        $html = $this->htmlPage(Inertia::render('Profile', ['user' => $model]));

        $this->createApp('/profile', 'GET', $this->inertiaHeaders());
        $json = $this->page(Inertia::render('Profile', ['user' => $model]));

        $this->assertSame(['id' => 7], $html['props']['user']);
        $this->assertSame($html, $json);
    }

    public function testInvalidUtf8FailsLoudly(): void
    {
        $this->createApp('/', 'GET', $this->inertiaHeaders());

        $this->expectException(JsonException::class);
        Inertia::render('Page', ['name' => "\xB1\x31"]);
    }

    public function testEmptyComponentThrows(): void
    {
        $this->createApp();

        $this->expectException(InvalidArgumentException::class);
        Inertia::render('');
    }

    public function testMissingRootViewThrows(): void
    {
        $this->createApp();
        Inertia::setRootView('@fixtures/views/missing.php');

        $this->expectException(\yii\base\ViewNotFoundException::class);
        Inertia::render('Page');
    }

    public function testEnumComponentsAndPropProviders(): void
    {
        $this->createApp('/', 'GET', $this->inertiaHeaders());
        $provider = new class () implements ProvidesInertiaProperties {
            public function toInertiaProperties(RenderContext $context): iterable
            {
                yield 'component' => $context->component;
            }
        };

        $page = $this->page(inertia(PageComponent::Dashboard, $provider));

        $this->assertSame('Dashboard/Index', $page['component']);
        $this->assertSame('Dashboard/Index', $page['props']['component']);
    }

    public function testSharedProps(): void
    {
        $evaluated = 0;
        $this->createApp('/', 'GET', $this->inertiaHeaders(), [
            'components' => ['inertia' => ['shared' => ['app' => 'config', 'overridden' => 'shared']]],
        ]);
        Inertia::share('lazy', function () use (&$evaluated) {
            $evaluated++;

            return 'lazy';
        });
        Inertia::share(['auth' => ['user' => ['name' => 'Ann']]]);
        Inertia::shareOnce('countries', fn () => ['NL']);

        $this->assertSame(0, $evaluated);
        $this->assertSame('Ann', Inertia::getShared('auth.user.name'));
        $this->assertSame('none', Inertia::getShared('missing', 'none'));

        $page = $this->page(Inertia::render('Page', ['overridden' => 'page']));

        $this->assertSame(1, $evaluated);
        $this->assertEquals(
            ['errors' => [], 'app' => 'config', 'overridden' => 'page', 'lazy' => 'lazy', 'auth' => ['user' => ['name' => 'Ann']], 'countries' => ['NL']],
            $page['props'],
        );
        $this->assertSame(['errors', 'app', 'overridden', 'lazy', 'auth', 'countries'], $page['sharedProps']);
        $this->assertSame(['countries' => ['prop' => 'countries', 'expiresAt' => null]], $page['onceProps']);

        Inertia::flushShared();
        $this->assertSame([], Inertia::getShared());
    }

    public function testPartialReloadFiltersSharedPropsButKeepsErrors(): void
    {
        $this->createApp('/', 'GET', $this->inertiaHeaders([
            'X-Inertia-Partial-Component' => 'Dashboard',
            'X-Inertia-Partial-Data' => 'stats',
        ]));
        Inertia::share('auth', fn () => $this->fail('Unrequested shared props must not be evaluated'));

        $page = $this->page(Inertia::render('Dashboard', ['stats' => [1], 'users' => fn () => $this->fail('not requested')]));

        $this->assertEquals(['errors' => [], 'stats' => [1]], $page['props']);
    }

    public function testVersion(): void
    {
        $this->createApp();
        $manifest = __DIR__ . '/../fixtures/web/dist/.vite/manifest.json';

        $this->assertSame(md5_file($manifest), Inertia::getVersion());

        Inertia::version(123);
        $this->assertSame('123', Inertia::getVersion());

        Inertia::version(fn () => filemtime($manifest));
        $this->assertSame((string) filemtime($manifest), Inertia::getVersion());

        Inertia::version(null);
        $this->assertSame(md5_file($manifest), Inertia::getVersion());

        $this->createApp('/', 'GET', [], ['components' => ['inertia' => ['vite' => ['buildPath' => '@fixtures/none']]]]);
        $this->assertSame('', Inertia::getVersion());
    }

    public function testValidationErrorsAreFlashedToTheNextPage(): void
    {
        $this->createApp('/users', 'POST', $this->inertiaHeaders());
        $model = DynamicModel::validateData(['email' => 'nope', 'name' => ''], [
            [['name'], 'required'],
            [['email'], 'email'],
            [['email'], 'string', 'min' => 10],
        ]);
        Inertia::withErrors($model);
        Inertia::withErrors(['name' => ['Name is taken', 'Name is too short'], 'extra' => 'Extra']);

        $this->createApp('/users/create', 'GET', $this->inertiaHeaders());
        $errors = $this->page(Inertia::render('Users/Create'))['props']['errors'];
        $this->assertSame(['name' => 'Name is taken', 'email' => 'Email is not a valid email address.', 'extra' => 'Extra'], $errors);

        $this->createApp('/users/create', 'GET', $this->inertiaHeaders());
        $this->assertStringContainsString('"errors":{}', Inertia::render('Users/Create')->data);
    }

    public function testAllValidationMessages(): void
    {
        $this->createApp('/users', 'POST', $this->inertiaHeaders(), ['components' => ['inertia' => ['withAllErrors' => true]]]);
        Inertia::withErrors(['name' => ['Name is taken', 'Name is too short']]);

        $errors = $this->page(Inertia::render('Users/Create'))['props']['errors'];

        $this->assertSame(['name' => ['Name is taken', 'Name is too short']], $errors);
    }

    public function testErrorBags(): void
    {
        $this->createApp('/', 'POST', $this->inertiaHeaders());
        Inertia::withErrors(['email' => 'Invalid']);

        $this->createApp('/', 'GET', $this->inertiaHeaders(['X-Inertia-Error-Bag' => 'login']));
        $this->assertSame(['login' => ['email' => 'Invalid']], $this->page(Inertia::render('Page'))['props']['errors']);

        $this->createApp('/', 'POST', $this->inertiaHeaders());
        Inertia::withErrors(['email' => 'Invalid'], 'login');

        $this->createApp('/', 'GET', $this->inertiaHeaders());
        $this->assertSame(['login' => ['email' => 'Invalid']], $this->page(Inertia::render('Page'))['props']['errors']);
    }

    public function testFlashData(): void
    {
        $this->createApp('/users', 'POST', $this->inertiaHeaders());
        Inertia::flash('message', 'Saved');
        Inertia::flash(['toast' => ['type' => 'success']]);
        $this->assertSame(['message' => 'Saved', 'toast' => ['type' => 'success']], $this->manager()->getFlashed());

        $this->createApp('/users', 'GET', $this->inertiaHeaders());
        $this->assertSame(['message' => 'Saved', 'toast' => ['type' => 'success']], $this->page(Inertia::render('Users'))['flash']);

        $this->createApp('/users', 'GET', $this->inertiaHeaders());
        $this->assertArrayNotHasKey('flash', $this->page(Inertia::render('Users')));
    }

    public function testRenderDoesNotStartASessionForNewVisitors(): void
    {
        $this->createApp();
        Inertia::render('Page');

        $this->assertFalse(Yii::$app->getSession()->getIsActive());
    }

    public function testHistoryAndFragmentFlagsAreOnlyPresentWhenEnabled(): void
    {
        $this->createApp('/', 'GET', $this->inertiaHeaders());
        $page = $this->page(Inertia::render('Home'));
        $this->assertArrayNotHasKey('clearHistory', $page);
        $this->assertArrayNotHasKey('encryptHistory', $page);
        $this->assertArrayNotHasKey('preserveFragment', $page);

        $this->createApp('/logout', 'POST', $this->inertiaHeaders());
        Inertia::clearHistory();
        Inertia::preserveFragment();

        $this->createApp('/', 'GET', $this->inertiaHeaders());
        Inertia::encryptHistory();
        $page = $this->page(Inertia::render('Home'));
        $this->assertTrue($page['clearHistory']);
        $this->assertTrue($page['encryptHistory']);
        $this->assertTrue($page['preserveFragment']);

        $this->createApp('/', 'GET', $this->inertiaHeaders(), ['components' => ['inertia' => ['encryptHistory' => true]]]);
        $page = $this->page(Inertia::render('Home'));
        $this->assertArrayNotHasKey('clearHistory', $page);
        $this->assertTrue($page['encryptHistory']);
    }

    public function testUrlResolver(): void
    {
        $this->createApp('/app/users', 'GET', $this->inertiaHeaders(), [
            'components' => ['inertia' => ['urlResolver' => fn (Request $request) => substr($request->getUrl(), 4)]],
        ]);

        $this->assertSame('/users', $this->page(Inertia::render('Users'))['url']);
    }

    public function testServerSideRendering(): void
    {
        $gateway = new FakeGateway(new SsrResponse('<title>SSR</title>', '<div id="app" data-server-rendered="true">Hi</div>'));
        $this->createApp('/', 'GET', [], ['components' => ['inertia' => ['ssrEnabled' => true, 'ssrGateway' => $gateway]]]);

        $response = Inertia::render('Home', ['name' => 'Ann']);

        $this->assertSame('Home', $gateway->dispatched['component']);
        $this->assertStringContainsString('<title>SSR</title>', $response->data);
        $this->assertStringContainsString('<div id="app" data-server-rendered="true">Hi</div>', $response->data);
        $this->assertStringNotContainsString('data-page', $response->data);
    }

    public function testServerSideRenderingFallsBackToClientRendering(): void
    {
        $gateway = new FakeGateway(null);
        $this->createApp('/', 'GET', [], ['components' => ['inertia' => ['ssrEnabled' => true, 'ssrGateway' => $gateway]]]);

        $this->assertSame('Home', $this->htmlPage(Inertia::render('Home'))['component']);
    }

    public function testServerSideRenderingCanBeDisabled(): void
    {
        $gateway = new FakeGateway(new SsrResponse('', '<div id="app">SSR</div>'));
        $config = ['components' => ['inertia' => ['ssrEnabled' => true, 'ssrGateway' => $gateway]]];

        $this->createApp('/', 'GET', $this->inertiaHeaders(), $config);
        Inertia::render('Home');
        $this->assertNull($gateway->dispatched, 'Inertia visits are never server-side rendered');

        $this->createApp('/admin/users', 'GET', [], $config);
        Inertia::withoutSsr(['admin/*']);
        Inertia::render('Admin/Users');
        $this->assertNull($gateway->dispatched);

        $this->createApp('/users', 'GET', [], $config);
        Inertia::disableSsr(fn (Request $request) => str_starts_with($request->getUrl(), '/users'));
        Inertia::render('Users');
        $this->assertNull($gateway->dispatched);

        $this->createApp('/', 'GET', [], $config);
        Inertia::disableSsr(false);
        Inertia::render('Home');
        $this->assertNotNull($gateway->dispatched);
    }

    public function testSsrGatewayUsesViteDevServer(): void
    {
        $this->createApp('/', 'GET', [], ['components' => ['inertia' => ['vite' => ['devServerUrl' => 'http://localhost:5173']]]]);

        $gateway = $this->manager()->getSsrGateway();

        $this->assertInstanceOf(HttpGateway::class, $gateway);
        $this->assertSame('http://localhost:5173', $gateway->devServerUrl);
    }

    public function testAppRootElement(): void
    {
        $this->createApp();

        $this->assertSame(
            '<script type="application/json" data-page="root">{"component":"X","url":"\/a"}</script><div id="root" class="h-full"></div>',
            Inertia::app(['component' => 'X', 'url' => '/a'], null, 'root', ['class' => 'h-full', 'id' => 'ignored']),
        );
        $this->assertSame('<main>SSR</main>', Inertia::app([], new SsrResponse('<title>x</title>', '<main>SSR</main>')));
        $this->assertSame('', Inertia::ssrHead(null));
    }

    public function testManagerIsRegisteredWhenNotConfigured(): void
    {
        $this->createApp();
        Yii::$app->clear('inertia');

        $this->assertInstanceOf(Manager::class, Inertia::getManager());
        $this->assertSame($this->manager(), Yii::$app->get('inertia'));
    }

    public function testMisconfiguredComponentThrows(): void
    {
        $this->createApp();
        Yii::$app->set('inertia', new \yii\base\Component());

        $this->expectException(InvalidConfigException::class);
        Inertia::getManager();
    }
}
