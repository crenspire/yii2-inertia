# Yii2 Inertia.js Adapter

[![CI](https://github.com/crenspire/yii2-inertia/actions/workflows/ci.yml/badge.svg)](https://github.com/crenspire/yii2-inertia/actions/workflows/ci.yml)

The server-side adapter for [Inertia.js](https://inertiajs.com) v3 in the [Yii 2](https://www.yiiframework.com) framework.
Build single-page apps with React, Vue or Svelte using ordinary Yii controllers, routing, validation and sessions — no API required.

- Complete Inertia v3 protocol: partial reloads, deferred, optional, once, merge and infinite scroll props, asset versioning, flash data, error bags, history encryption, fragment redirects
- Works with Yii out of the box: redirects, CSRF protection, JSON form submissions and validation errors are handled for you
- Vite integration (build manifest and dev server with HMR) and server-side rendering
- Zero configuration to get started — the component bootstraps itself

> Upgrading from 1.x? See [UPGRADE.md](UPGRADE.md).

## Requirements

- PHP 8.1+
- Yii 2.0.55+
- An Inertia.js v3 client adapter (`@inertiajs/react`, `@inertiajs/vue3` or `@inertiajs/svelte`)

## Installation

```bash
composer require crenspire/yii2-inertia
```

The `inertia` application component is registered and bootstrapped automatically through Yii's extension bootstrapping.
Configure it only when you need to change the defaults (see [Configuration](#configuration)).

### Root view

Inertia pages are rendered into a root view on the first visit. Copy [`stubs/inertia.php`](stubs/inertia.php) to
`views/layouts/inertia.php`:

```php
<?php
use Crenspire\Yii2Inertia\Inertia;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var array $page */
/** @var Crenspire\Yii2Inertia\Ssr\SsrResponse|null $ssr */

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= Html::encode(Yii::$app->language) ?>">
<head>
    <meta charset="<?= Html::encode(Yii::$app->charset) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title data-inertia><?= Html::encode(Yii::$app->name) ?></title>
    <?= Inertia::vite()->tags('src/main.jsx') ?>
    <?= Inertia::ssrHead($ssr) ?>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
<?= Inertia::app($page, $ssr) ?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
```

`Inertia::app()` outputs the page data (`<script data-page="app" type="application/json">`) and the `<div id="app">`
the client mounts on.

### Frontend

```bash
npm install @inertiajs/react react react-dom
npm install --save-dev vite @vitejs/plugin-react
```

```js
// vite.config.js
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig(({ command }) => ({
  plugins: [react()],
  base: command === 'build' ? '/dist/' : '/',
  build: {
    outDir: '../web/dist', // served by Yii from @webroot/dist
    manifest: true,
    rollupOptions: { input: 'src/main.jsx' },
  },
  server: { origin: 'http://localhost:5173' },
}))
```

```jsx
// src/main.jsx
import { createInertiaApp } from '@inertiajs/react'
import { createRoot } from 'react-dom/client'

createInertiaApp({
  resolve: (name) => import.meta.glob('./pages/**/*.jsx', { eager: true })[`./pages/${name}.jsx`],
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />)
  },
})
```

## Usage

### Rendering pages

```php
use Crenspire\Yii2Inertia\Inertia;

class UserController extends \yii\web\Controller
{
    public function actionIndex(): \yii\web\Response
    {
        return Inertia::render('Users/Index', [
            'users' => fn () => User::find()->select(['id', 'name', 'email'])->asArray()->all(),
            'filters' => Yii::$app->request->get(),
        ]);
    }
}
```

The global `inertia()` helper is a shortcut: `return inertia('Users/Index', [...]);`. Component names can also be
string-backed enums. A third argument passes extra variables to the root view: `Inertia::render('Home', $props, ['title' => 'Home'])`.

Closures are evaluated only when the prop is actually sent, and objects implementing `yii\base\Arrayable`
(models, active records) are converted with `toArray()`.

> **Security:** everything in props is sent to the browser. Passing a model sends all fields returned by its
> `fields()` method — for an `ActiveRecord` identity that includes columns such as `password_hash` and `auth_key`.
> Select the attributes explicitly (`$user->toArray(['id', 'name'])`) or override `fields()`.

### Shared props

Props available on every page:

```php
// config/web.php
'components' => [
    'inertia' => [
        'shared' => [
            'appName' => 'My App',
            'auth.user' => fn () => Yii::$app->user->identity?->toArray(['id', 'name']),
        ],
    ],
],
```

or at runtime, for example in a base controller's `init()`:

```php
Inertia::share('locale', Yii::$app->language);
Inertia::share(['permissions' => fn () => $this->permissions()]);
Inertia::shareOnce('countries', fn () => Country::find()->asArray()->all());
```

Dot-notation keys create nested props. The `errors` prop is always shared.

### Forms, validation and redirects

Inertia submits forms as JSON; the adapter registers Yii's `JsonParser`, so `Yii::$app->request->post()` just works.
Use regular Yii redirects after a submission:

```php
public function actionStore(): \yii\web\Response
{
    $model = new User();
    if (!$model->load(Yii::$app->request->post(), '') || !$model->save()) {
        Inertia::withErrors($model);   // exposed as the `errors` prop of the next page

        return Inertia::back();        // redirect to the previous page
    }

    Inertia::flash('success', 'User created.');

    return $this->redirect(['user/index']);
}
```

The adapter takes care of the protocol details:

- `$this->redirect()` works for Inertia requests (Yii would otherwise replace the `Location` header with `X-Redirect` for AJAX requests)
- `302` redirects after `PUT`, `PATCH` and `DELETE` become `303 See Other`
- redirects to a URL with a `#fragment` are converted to an `X-Inertia-Redirect` response
- an action that returns nothing for an Inertia request redirects back

`withErrors()` accepts a model or an `attribute => message(s)` array and an optional error bag name. Only the first
message per attribute is sent unless `withAllErrors` is enabled.

Use `Inertia::location($url)` for redirects that must leave the Inertia app (external URLs, non-Inertia pages); it
responds with `409 Conflict` and `X-Inertia-Location` to Inertia requests.

### Flash data

```php
Inertia::flash('toast', ['type' => 'success', 'message' => 'Saved']);
```

Flash data is delivered to the next rendered page as `page.flash` (not as a prop, so it is not stored in the browser history).
Yii's own session flashes can still be shared as props if you prefer.

### CSRF protection

No setup is needed. The adapter puts the CSRF token in a JavaScript-readable `XSRF-TOKEN` cookie, which the Inertia
HTTP client sends back in the `X-XSRF-TOKEN` header, and Yii validates it as usual.

### Special props

```php
return Inertia::render('Dashboard', [
    // Always evaluated.
    'stats' => fn () => Stats::summary(),

    // Only evaluated when requested: router.reload({ only: ['report'] })
    'report' => Inertia::optional(fn () => Report::build()),

    // Loaded by the client in a separate request after the page has rendered.
    'activity' => Inertia::defer(fn () => Activity::latest()),
    'permissions' => Inertia::defer(fn () => Permissions::all(), 'sidebar', rescue: true),

    // Included even in partial reloads that did not request it.
    'notifications' => Inertia::always(fn () => Notification::unreadCount()),

    // Merged into the existing client-side value.
    'messages' => Inertia::merge(fn () => $messages)->matchOn('id'),
    'alerts' => Inertia::merge(fn () => $alerts)->prepend(),
    'settings' => Inertia::deepMerge(fn () => $settings),

    // Loaded once and remembered by the client across pages.
    'plans' => Inertia::once(fn () => Plan::all())->until(3600),

    // Paginated list for <InfiniteScroll>, from any paginated data provider.
    'posts' => Inertia::scroll(fn () => new ActiveDataProvider(['query' => Post::find()])),
]);
```

Special props can be nested at any depth, and partial reloads accept dot-notation paths (`only: ['auth.user']`).
Classes implementing `ProvidesInertiaProperties` (several props) or `ProvidesInertiaProperty` (one value) can be used as props too.

### Asset versioning

When the client's asset version differs from the server's, the next visit is a full page load. By default the version
is a hash of the Vite manifest. Set it explicitly when you use another build tool:

```php
Inertia::version(fn () => md5_file(Yii::getAlias('@webroot/assets/manifest.json')));
```

### History encryption

```php
Inertia::encryptHistory();   // or 'encryptHistory' => true in the component config
Inertia::clearHistory();     // e.g. on logout
```

## Vite

`Inertia::vite()->tags('src/main.jsx')` renders the tags for one or more entry points: in production from the
build manifest (including CSS of imported chunks and `modulepreload` links), in development from the Vite dev server.

```php
'inertia' => [
    'vite' => [
        'buildPath' => '@webroot/dist',   // build.outDir
        'baseUrl' => '@web/dist',
        'devServerUrl' => YII_ENV_DEV ? 'http://localhost:5173' : null,
        'reactRefresh' => true,           // for @vitejs/plugin-react
    ],
],
```

`Inertia::vite()->asset('src/images/logo.svg')` returns the URL of an asset processed by Vite.
Script tags respect the view's `scriptOptions`, so a CSP nonce is applied automatically.

## Server-side rendering

1. Create an SSR entry point with `createServer()` from your Inertia client adapter and build it.
2. Start the SSR server (`node dist/ssr.js`, port 13714 by default).
3. Enable SSR:

```php
'inertia' => [
    'ssrEnabled' => true,
    'ssrExcept' => ['admin/*'],
    'ssrGateway' => [
        'class' => \Crenspire\Yii2Inertia\Ssr\HttpGateway::class,
        'url' => 'http://127.0.0.1:13714',
        'bundle' => '@app/frontend/dist/ssr.js', // optional: skip SSR when the bundle is missing
    ],
],
```

When the Vite dev server is configured, pages are rendered through the `@inertiajs/vite` plugin's dev endpoint instead.
If rendering fails the error is logged and the page falls back to client-side rendering (set `throwOnError` on the
gateway to throw instead). `Inertia::disableSsr()` and `Inertia::withoutSsr('path/*')` control SSR at runtime.

## Configuration

All properties of the `inertia` component (`Crenspire\Yii2Inertia\Manager`):

| Property | Default | Description |
|---|---|---|
| `rootView` | `@app/views/layouts/inertia.php` | View rendered on the first visit |
| `version` | `null` | Asset version: string, int or closure. `null` uses the Vite manifest hash |
| `shared` | `[]` | Props shared with every page |
| `encryptHistory` | `false` | Encrypt the history state of every page |
| `withAllErrors` | `false` | Send all validation messages per attribute instead of the first one |
| `exposeSharedPropKeys` | `true` | List shared prop keys in the page object (`sharedProps`) |
| `urlResolver` | `null` | `fn (Request $request): string` to customize the page URL |
| `enableCsrfCookie` | `true` | Send the `XSRF-TOKEN` cookie and accept the `X-XSRF-TOKEN` header |
| `csrfCookieName` / `csrfHeaderName` | `XSRF-TOKEN` / `X-XSRF-TOKEN` | Change these if another app on the same host uses the same cookie name |
| `registerJsonParser` | `true` | Register `JsonParser` for JSON request bodies |
| `ssrEnabled` | `false` | Enable SSR (bool or `fn (Request $request): bool`) |
| `ssrExcept` | `[]` | URL path patterns never server-side rendered |
| `ssrGateway` | `HttpGateway` | SSR gateway object, component ID or configuration |
| `vite` | `[]` | `Vite` helper configuration |

If your application does not use Yii's extension bootstrapping (the `yiisoft/yii2-composer` plugin), add the component
to the `bootstrap` list manually: `'bootstrap' => ['inertia']`.

## Testing

In functional tests, send the `X-Inertia: true` header and decode the JSON page object:

```php
$page = json_decode(Yii::$app->response->data, true);
$this->assertSame('Users/Index', $page['component']);
```

`Inertia::getManager()->createPage('Users/Index', $props)` builds a page object without rendering.

## Example application

[`examples/basic`](examples/basic) is a complete application with React 19, Vite and Tailwind CSS, demonstrating deferred
props, partial reloads, forms with validation errors, flash data and infinite scroll.

```bash
cd examples/basic
composer install
cd vite && npm install && npm run build && cd ..
php -S localhost:8080 -t web web/router.php
```

For hot module replacement run `npm run dev` in `vite/` and start PHP with
`VITE_DEV_SERVER=http://localhost:5173 php -S localhost:8080 -t web web/router.php`.

## Development

```bash
composer install
composer test
```

## License

MIT License. See [LICENSE](LICENSE).

See [CONTRIBUTING.md](CONTRIBUTING.md) for contribution guidelines and [CHANGELOG.md](CHANGELOG.md) for the release history.
