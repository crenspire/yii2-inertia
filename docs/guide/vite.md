# Vite

The adapter includes a Vite helper that renders the tags for your entry points, from the build manifest in production
and from the Vite dev server in development.

```php
<?= Inertia::vite()->tags('src/main.jsx') ?>
```

## Configuration

```php
// config/web.php
'components' => [
    'inertia' => [
        'vite' => [
            'buildPath' => '@webroot/dist',     // Vite's build.outDir
            'baseUrl' => '@web/dist',           // public URL of buildPath
            'devServerUrl' => YII_ENV_DEV ? 'http://localhost:5173' : null,
            'reactRefresh' => true,             // React only
        ],
    ],
],
```

See the [Vite helper reference](/reference/vite) for all options.

Vite must write a manifest (`build.manifest: true`). With `base: '/dist/'` for builds, URLs of assets referenced from
CSS and JavaScript point at the right place:

```js
export default defineConfig(({ command }) => ({
  base: command === 'build' ? '/dist/' : '/',
  build: {
    outDir: '../web/dist',
    manifest: true,
    rollupOptions: { input: 'src/main.jsx' },
  },
}))
```

## Production

In production `tags()` reads the manifest and renders, for each entry point:

- `<link rel="modulepreload">` for imported JavaScript chunks,
- `<link rel="stylesheet">` for the CSS of the entry point and of all imported chunks,
- `<script type="module">` for the entry point.

```html
<link href="/dist/assets/vendor-D8f2a1.js" rel="modulepreload">
<link href="/dist/assets/main-Ck3s9q.css" rel="stylesheet">
<script type="module" src="/dist/assets/main-B2x7Hk.js"></script>
```

CSS files can be entry points too: `Inertia::vite()->tags(['src/main.jsx', 'src/print.css'])`.

## Development server

When `devServerUrl` is set, `tags()` loads the entry points from the dev server, including the Vite client for hot
module replacement:

```html
<script type="module" src="http://localhost:5173/@vite/client"></script>
<script type="module" src="http://localhost:5173/src/main.jsx"></script>
```

Set `reactRefresh` when using `@vitejs/plugin-react`: it adds the React Fast Refresh preamble, which the plugin cannot
inject into HTML served by Yii.

Configure the dev server so asset URLs point at it and it accepts requests from your application:

```js
server: {
  port: 5173,
  strictPort: true,
  origin: 'http://localhost:5173',
},
```

A common setup reads the URL from the environment, so the dev server is only used when it is running:

```php
'devServerUrl' => YII_ENV_DEV ? (getenv('VITE_DEV_SERVER') ?: null) : null,
```

## Other assets

`asset()` returns the URL of a file processed by Vite, for example an image imported by your code:

```php
<img src="<?= Inertia::vite()->asset('src/images/logo.svg') ?>" alt="">
```

## Content Security Policy

Script tags use the view's `scriptOptions`. When you set a nonce there, it is applied to the tags the helper renders:

```php
Yii::$app->view->scriptOptions = ['nonce' => $nonce];
```

## Missing manifest

A missing manifest throws an exception, so a deployment without built assets is noticed immediately. Test environments
that render pages without building the frontend can disable this:

```php
// config/test.php
'inertia' => ['vite' => ['throwOnMissingManifest' => false]],
```

## Asset version

The hash of the manifest is the default [asset version](/guide/asset-versioning).
