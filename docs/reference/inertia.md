# Inertia facade

`Crenspire\Yii2Inertia\Inertia` is a static facade for the `inertia` application component
(`Crenspire\Yii2Inertia\Manager`). Methods marked *component* are also available on the component itself, e.g.
`Yii::$app->inertia->render()`.

## Rendering

### render()

```php
Inertia::render(string|BackedEnum $component, array|ProvidesInertiaProperties $props = [], array $viewData = []): Response
```

Renders a page: the root view on the first visit, the JSON page object on Inertia visits. *Component.*
See [pages and props](/guide/responses).

The global helper `inertia()` has the same signature.

### location()

```php
Inertia::location(string|array $url): Response
```

Redirects with a full page visit: `409 Conflict` with `X-Inertia-Location` for Inertia requests, a `302` redirect
otherwise. Accepts a URL or a route for `Url::to()`. *Component.*

### back()

```php
Inertia::back(string|array $fallback = ['/']): Response
```

Redirects to the referrer, or to the fallback URL or route, with `303` after `PUT`/`PATCH`/`DELETE`. *Component.*

### setRootView() / getRootView()

```php
Inertia::setRootView(string $view): void
Inertia::getRootView(): string
```

## Shared data

### share()

```php
Inertia::share(string|array|ProvidesInertiaProperties $key, mixed $value = null): void
```

Shares one prop, an array of props, or a props provider with every page. *Component.*

### shareOnce()

```php
Inertia::shareOnce(string $key, callable $callback): OnceProp
```

Shares a [once prop](/guide/once-props). Returns the prop so it can be configured (`->until()`, `->as()`). *Component.*

### getShared()

```php
Inertia::getShared(?string $key = null, mixed $default = null): mixed
```

Returns a shared prop (unevaluated, dot notation supported) or all shared props. *Component.*

### flushShared()

```php
Inertia::flushShared(): void
```

## Session data

### withErrors()

```php
Inertia::withErrors(Model|array $errors, string $bag = 'default'): void
```

Flashes validation errors for the next rendered page. See [forms and validation](/guide/forms). *Component.*

### flash()

```php
Inertia::flash(string|array $key, mixed $value = null): void
```

Flashes data for the next rendered page. See [flash data](/guide/flash-data). *Component.*
`Yii::$app->inertia->getFlashed()` returns the data flashed so far.

### clearHistory()

```php
Inertia::clearHistory(): void
```

Clears the encrypted history on the next rendered page. *Component.*

### preserveFragment()

```php
Inertia::preserveFragment(): void
```

Keeps the URL fragment of the current visit across a redirect. *Component.*

## History

### encryptHistory()

```php
Inertia::encryptHistory(bool $encrypt = true): void
```

## Asset versioning

### version()

```php
Inertia::version(string|int|Closure|null $version): void
```

Sets the asset version; `null` restores the default.

### getVersion()

```php
Inertia::getVersion(): string
```

*Component.*

## Prop types

See [prop types](/reference/props).

```php
Inertia::optional(callable $callback): OptionalProp
Inertia::defer(callable $callback, string $group = 'default', bool $rescue = false): DeferProp
Inertia::always(mixed $value): AlwaysProp
Inertia::merge(mixed $value): MergeProp
Inertia::deepMerge(mixed $value): MergeProp
Inertia::once(callable $callback): OnceProp
Inertia::scroll(mixed $value, string $wrapper = 'data', ProvidesScrollMetadata|callable|null $metadata = null): ScrollProp
```

## Server-side rendering

### disableSsr()

```php
Inertia::disableSsr(bool|Closure $condition = true): void
```

Disables SSR, optionally based on `fn (Request $request): bool`.

### withoutSsr()

```php
Inertia::withoutSsr(string|array $paths): void
```

Excludes URL path patterns from SSR.

## Root view helpers

### app()

```php
Inertia::app(array $page, ?SsrResponse $ssr = null, string $id = 'app', array $options = []): string
```

Renders the page data script and the root element (`$options` are its HTML attributes), or the server-rendered body.

### ssrHead()

```php
Inertia::ssrHead(?SsrResponse $ssr): string
```

Renders the head elements produced by server-side rendering, or an empty string.

### vite()

```php
Inertia::vite(): Vite
```

Returns the [Vite helper](/reference/vite).

## Utilities

### isInertiaRequest()

```php
Inertia::isInertiaRequest(?Request $request = null): bool
```

Whether the (current) request was sent by the Inertia client. *Component.*

### getManager()

```php
Inertia::getManager(): Manager
```

Returns the `inertia` component, registering it with defaults when it is not configured.

## Manager-only methods

### createPage()

```php
Yii::$app->inertia->createPage(string $component, array|ProvidesInertiaProperties $props = [], ?Request $request = null): array
```

Builds the page object without rendering a response.

### encodePage()

```php
Yii::$app->inertia->encodePage(array $page): string
```

Encodes a page object as JSON that is safe to embed in a `<script>` element. Throws `JsonException` for invalid data.

### getSsrGateway() / getVite()

Return the configured SSR gateway and Vite helper instances.
