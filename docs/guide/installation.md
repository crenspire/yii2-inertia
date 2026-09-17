# Installation

Setting up Inertia takes three steps: install the adapter, create a root view, and set up the frontend with Vite.

## Server side

### Install the package

```bash
composer require crenspire/yii2-inertia:^2.0
```

::: warning Before the 2.0.0 release
Until version 2.0.0 is tagged, install the development version with
`composer require crenspire/yii2-inertia:2.0.x-dev`.
:::

Yii installs `bower-asset` packages through [Asset Packagist](https://asset-packagist.org). Application templates
already include it; if yours does not, add the repository to `composer.json`:

```json
"repositories": [
    { "type": "composer", "url": "https://asset-packagist.org" }
]
```

The package registers an `inertia` application component and bootstraps it automatically through Yii's extension
bootstrapping, so no configuration is required to get started.

::: details Applications without extension bootstrapping
Extension bootstrapping relies on the `yiisoft/yii2-composer` plugin, which every Yii application template uses.
If your application does not use it, register and bootstrap the component yourself:

```php
// config/web.php
'bootstrap' => ['inertia'],
'components' => [
    'inertia' => ['class' => \Crenspire\Yii2Inertia\Manager::class],
],
```
:::

### Create the root view

The root view is rendered on the first visit. It loads your assets and outputs the page data. Create
`views/layouts/inertia.php` (the default location, see [`rootView`](/reference/configuration#rootview)):

```php
<?php

use Crenspire\Yii2Inertia\Inertia;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var array $page the Inertia page object */
/** @var Crenspire\Yii2Inertia\Ssr\SsrResponse|null $ssr the server-side rendered page, if SSR is enabled */

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

- `Inertia::vite()->tags()` outputs the script and stylesheet tags for your entry point (see [Vite](/guide/vite)).
- `Inertia::app()` outputs the page data (`<script data-page="app" type="application/json">`) and the
  `<div id="app">` element the client mounts on.
- The `data-inertia` attribute lets the client manage the `<title>` with its `Head` component.

A copy of this file is available in [`stubs/inertia.php`](https://github.com/crenspire/yii2-inertia/blob/develop/stubs/inertia.php).

### Render a page

```php
namespace app\controllers;

use Crenspire\Yii2Inertia\Inertia;
use yii\web\Controller;
use yii\web\Response;

class SiteController extends Controller
{
    public function actionIndex(): Response
    {
        return Inertia::render('Home', [
            'message' => 'Hello from Yii!',
        ]);
    }
}
```

## Client side

The examples use a `frontend/` directory next to `web/`, with Vite building into `web/dist`. Any layout works as long
as [`vite.buildPath`](/reference/vite#buildpath) points at the build directory.

### Install the dependencies

::: code-group

```bash [React]
npm install @inertiajs/react react react-dom
npm install --save-dev vite @vitejs/plugin-react
```

```bash [Vue]
npm install @inertiajs/vue3 vue
npm install --save-dev vite @vitejs/plugin-vue
```

```bash [Svelte]
npm install @inertiajs/svelte svelte
npm install --save-dev vite @sveltejs/vite-plugin-svelte
```

:::

### Configure Vite

::: code-group

```js [React]
// frontend/vite.config.js
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig(({ command }) => ({
  plugins: [react()],
  // Built assets are served by Yii from web/dist; the dev server serves them from its root.
  base: command === 'build' ? '/dist/' : '/',
  build: {
    outDir: '../web/dist',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: { input: 'src/main.jsx' },
  },
  server: {
    port: 5173,
    strictPort: true,
    origin: 'http://localhost:5173',
  },
}))
```

```js [Vue]
// frontend/vite.config.js
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig(({ command }) => ({
  plugins: [vue()],
  base: command === 'build' ? '/dist/' : '/',
  build: {
    outDir: '../web/dist',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: { input: 'src/main.js' },
  },
  server: {
    port: 5173,
    strictPort: true,
    origin: 'http://localhost:5173',
  },
}))
```

```js [Svelte]
// frontend/vite.config.js
import { defineConfig } from 'vite'
import { svelte } from '@sveltejs/vite-plugin-svelte'

export default defineConfig(({ command }) => ({
  plugins: [svelte()],
  base: command === 'build' ? '/dist/' : '/',
  build: {
    outDir: '../web/dist',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: { input: 'src/main.js' },
  },
  server: {
    port: 5173,
    strictPort: true,
    origin: 'http://localhost:5173',
  },
}))
```

:::

`build.manifest` is required: the adapter reads the manifest to find the hashed file names.

### Initialize the app

::: code-group

```jsx [React]
// frontend/src/main.jsx
import { createInertiaApp } from '@inertiajs/react'
import { createRoot } from 'react-dom/client'

createInertiaApp({
  resolve: (name) => {
    const pages = import.meta.glob('./pages/**/*.jsx', { eager: true })
    return pages[`./pages/${name}.jsx`]
  },
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />)
  },
})
```

```js [Vue]
// frontend/src/main.js
import { createInertiaApp } from '@inertiajs/vue3'
import { createApp, h } from 'vue'

createInertiaApp({
  resolve: (name) => {
    const pages = import.meta.glob('./pages/**/*.vue', { eager: true })
    return pages[`./pages/${name}.vue`]
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el)
  },
})
```

```js [Svelte]
// frontend/src/main.js
import { createInertiaApp } from '@inertiajs/svelte'
import { mount } from 'svelte'

createInertiaApp({
  resolve: (name) => {
    const pages = import.meta.glob('./pages/**/*.svelte', { eager: true })
    return pages[`./pages/${name}.svelte`]
  },
  setup({ el, App, props }) {
    mount(App, { target: el, props })
  },
})
```

:::

For Vue and Svelte, use `src/main.js` as the entry point in the root view: `Inertia::vite()->tags('src/main.js')`.

### Create the page component

::: code-group

```jsx [React]
// frontend/src/pages/Home.jsx
import { Head } from '@inertiajs/react'

export default function Home({ message }) {
  return (
    <>
      <Head title="Home" />
      <h1>{message}</h1>
    </>
  )
}
```

```vue [Vue]
<!-- frontend/src/pages/Home.vue -->
<script setup>
import { Head } from '@inertiajs/vue3'

defineProps({ message: String })
</script>

<template>
  <Head title="Home" />
  <h1>{{ message }}</h1>
</template>
```

```svelte [Svelte]
<!-- frontend/src/pages/Home.svelte -->
<script>
  let { message } = $props()
</script>

<svelte:head>
  <title>Home</title>
</svelte:head>

<h1>{message}</h1>
```

:::

## Run the application

Build the assets once, or start the Vite dev server for hot module replacement:

```bash
cd frontend
npm run build        # production build into web/dist
# or
npx vite             # dev server on http://localhost:5173
```

To use the dev server, point the Vite helper at it in development (see [Vite](/guide/vite#development-server)):

```php
// config/web.php
'components' => [
    'inertia' => [
        'vite' => [
            'devServerUrl' => YII_ENV_DEV ? 'http://localhost:5173' : null,
            'reactRefresh' => true, // React only
        ],
    ],
],
```

Then open your application. The first visit renders the root view, and every link or form handled by Inertia is now a
single-page navigation.

::: tip Example application
The repository contains a complete [example application](https://github.com/crenspire/yii2-inertia/tree/develop/examples/basic)
with React 19, Vite and Tailwind CSS, demonstrating deferred props, partial reloads, forms, flash data and infinite scroll.
:::
