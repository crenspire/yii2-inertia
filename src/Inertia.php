<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia;

use Closure;
use Crenspire\Yii2Inertia\Props\AlwaysProp;
use Crenspire\Yii2Inertia\Props\DeferProp;
use Crenspire\Yii2Inertia\Props\MergeProp;
use Crenspire\Yii2Inertia\Props\OnceProp;
use Crenspire\Yii2Inertia\Props\OptionalProp;
use Crenspire\Yii2Inertia\Props\ProvidesInertiaProperties;
use Crenspire\Yii2Inertia\Props\ProvidesScrollMetadata;
use Crenspire\Yii2Inertia\Props\ScrollProp;
use Crenspire\Yii2Inertia\Ssr\SsrResponse;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\helpers\Html;
use yii\web\Request;
use yii\web\Response;

/**
 * Static facade for the `inertia` application component ({@see Manager}).
 *
 * ```php
 * return Inertia::render('Users/Index', [
 *     'users' => fn () => User::find()->select(['id', 'name'])->asArray()->all(),
 * ]);
 * ```
 */
final class Inertia
{
    public const COMPONENT_ID = 'inertia';

    private function __construct()
    {
    }

    /**
     * Returns the `inertia` application component, registering it with defaults when it is not configured.
     */
    public static function getManager(): Manager
    {
        if (!Yii::$app->has(self::COMPONENT_ID)) {
            Yii::$app->set(self::COMPONENT_ID, ['class' => Manager::class]);
        }

        $manager = Yii::$app->get(self::COMPONENT_ID);
        if (!$manager instanceof Manager) {
            throw new InvalidConfigException(sprintf(
                'The "%s" application component must be an instance of %s.',
                self::COMPONENT_ID,
                Manager::class,
            ));
        }

        return $manager;
    }

    /**
     * @param array<int|string, mixed>|ProvidesInertiaProperties $props
     * @param array<string, mixed> $viewData extra variables passed to the root view
     */
    public static function render(string|\BackedEnum $component, array|ProvidesInertiaProperties $props = [], array $viewData = []): Response
    {
        return self::getManager()->render($component, $props, $viewData);
    }

    public static function share(string|array|ProvidesInertiaProperties $key, mixed $value = null): void
    {
        self::getManager()->share($key, $value);
    }

    public static function shareOnce(string $key, callable $callback): OnceProp
    {
        return self::getManager()->shareOnce($key, $callback);
    }

    public static function getShared(?string $key = null, mixed $default = null): mixed
    {
        return self::getManager()->getShared($key, $default);
    }

    public static function flushShared(): void
    {
        self::getManager()->flushShared();
    }

    /**
     * Sets the asset version. Pass null to use the default (a hash of the Vite manifest).
     */
    public static function version(string|int|Closure|null $version): void
    {
        self::getManager()->version = $version;
    }

    public static function getVersion(): string
    {
        return self::getManager()->getVersion();
    }

    public static function setRootView(string $view): void
    {
        self::getManager()->rootView = $view;
    }

    public static function getRootView(): string
    {
        return self::getManager()->rootView;
    }

    /**
     * @param string|array<int|string, mixed> $url
     */
    public static function location(string|array $url): Response
    {
        return self::getManager()->location($url);
    }

    /**
     * @param string|array<int|string, mixed> $fallback
     */
    public static function back(string|array $fallback = ['/']): Response
    {
        return self::getManager()->back($fallback);
    }

    /**
     * @param Model|array<string, string|list<string>> $errors
     */
    public static function withErrors(Model|array $errors, string $bag = 'default'): void
    {
        self::getManager()->withErrors($errors, $bag);
    }

    /**
     * @param string|array<string, mixed> $key
     */
    public static function flash(string|array $key, mixed $value = null): void
    {
        self::getManager()->flash($key, $value);
    }

    public static function encryptHistory(bool $encrypt = true): void
    {
        self::getManager()->encryptHistory = $encrypt;
    }

    public static function clearHistory(): void
    {
        self::getManager()->clearHistory();
    }

    public static function preserveFragment(): void
    {
        self::getManager()->preserveFragment();
    }

    /**
     * Disables server-side rendering, optionally based on a condition `fn (Request $request): bool`.
     */
    public static function disableSsr(bool|Closure $condition = true): void
    {
        self::getManager()->ssrEnabled = $condition instanceof Closure
            ? static fn (Request $request) => !$condition($request)
            : !$condition;
    }

    /**
     * Excludes URL paths (e.g. `admin/*`) from server-side rendering.
     *
     * @param string|list<string> $paths
     */
    public static function withoutSsr(string|array $paths): void
    {
        $manager = self::getManager();
        $manager->ssrExcept = array_merge($manager->ssrExcept, (array) $paths);
    }

    public static function isInertiaRequest(?Request $request = null): bool
    {
        return self::getManager()->isInertiaRequest($request);
    }

    /**
     * A prop that is only evaluated when explicitly requested by a partial reload.
     */
    public static function optional(callable $callback): OptionalProp
    {
        return new OptionalProp($callback);
    }

    /**
     * A prop that the client fetches in a separate request right after the page has rendered.
     *
     * @param bool $rescue whether to omit the prop (listing it in `rescuedProps`) instead of failing when it throws
     */
    public static function defer(callable $callback, string $group = 'default', bool $rescue = false): DeferProp
    {
        return new DeferProp($callback, $group, $rescue);
    }

    /**
     * A prop that is included in every response, even partial reloads that did not request it.
     */
    public static function always(mixed $value): AlwaysProp
    {
        return new AlwaysProp($value);
    }

    /**
     * A prop that the client appends to (or, with `->prepend()`, prepends to) the existing value.
     */
    public static function merge(mixed $value): MergeProp
    {
        return new MergeProp($value);
    }

    /**
     * A prop that the client deep-merges into the existing value.
     */
    public static function deepMerge(mixed $value): MergeProp
    {
        return (new MergeProp($value))->deepMerge();
    }

    /**
     * A prop that the client loads once and then remembers across page visits.
     */
    public static function once(callable $callback): OnceProp
    {
        return new OnceProp($callback);
    }

    /**
     * A paginated prop for the `<InfiniteScroll>` component, typically a data provider.
     *
     * @param ProvidesScrollMetadata|callable|null $metadata
     */
    public static function scroll(mixed $value, string $wrapper = 'data', ProvidesScrollMetadata|callable|null $metadata = null): ScrollProp
    {
        return new ScrollProp($value, $wrapper, $metadata);
    }

    /**
     * Renders the application root for the root view: the page data script and the element the
     * client mounts on, or the server-side rendered markup.
     *
     * @param array<string, mixed> $page the `$page` variable of the root view
     * @param array<string, mixed> $options HTML attributes of the root element
     */
    public static function app(array $page, ?SsrResponse $ssr = null, string $id = 'app', array $options = []): string
    {
        if ($ssr !== null) {
            return $ssr->body;
        }

        return Html::tag('script', self::getManager()->encodePage($page), ['data-page' => $id, 'type' => 'application/json'])
            . Html::tag('div', '', array_merge($options, ['id' => $id]));
    }

    /**
     * Renders the `<head>` elements produced by server-side rendering, if any.
     */
    public static function ssrHead(?SsrResponse $ssr): string
    {
        return $ssr?->head ?? '';
    }

    public static function vite(): Vite
    {
        return self::getManager()->getVite();
    }
}
