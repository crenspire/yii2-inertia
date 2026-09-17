# Server-side rendering

Server-side rendering (SSR) renders the first visit on the server, so the HTML already contains the page content. It
improves the perceived load time and helps crawlers that do not execute JavaScript. Subsequent visits are rendered in
the browser as usual.

SSR runs your page components in Node.js. The adapter sends the page object to an SSR server over HTTP and embeds the
returned HTML in the root view.

## 1. Create the SSR entry point

::: code-group

```jsx [React]
// frontend/src/ssr.jsx
import { createInertiaApp } from '@inertiajs/react'
import createServer from '@inertiajs/react/server'
import ReactDOMServer from 'react-dom/server'

createServer((page) =>
  createInertiaApp({
    page,
    render: ReactDOMServer.renderToString,
    resolve: (name) => {
      const pages = import.meta.glob('./pages/**/*.jsx', { eager: true })
      return pages[`./pages/${name}.jsx`]
    },
    setup: ({ App, props }) => <App {...props} />,
  }),
)
```

```js [Vue]
// frontend/src/ssr.js
import { createInertiaApp } from '@inertiajs/vue3'
import createServer from '@inertiajs/vue3/server'
import { createSSRApp, h } from 'vue'
import { renderToString } from 'vue/server-renderer'

createServer((page) =>
  createInertiaApp({
    page,
    render: renderToString,
    resolve: (name) => {
      const pages = import.meta.glob('./pages/**/*.vue', { eager: true })
      return pages[`./pages/${name}.vue`]
    },
    setup({ App, props, plugin }) {
      return createSSRApp({ render: () => h(App, props) }).use(plugin)
    },
  }),
)
```

```js [Svelte]
// frontend/src/ssr.js
import { createInertiaApp } from '@inertiajs/svelte'
import createServer from '@inertiajs/svelte/server'
import { render } from 'svelte/server'

createServer((page) =>
  createInertiaApp({
    page,
    resolve: (name) => {
      const pages = import.meta.glob('./pages/**/*.svelte', { eager: true })
      return pages[`./pages/${name}.svelte`]
    },
    setup({ App, props }) {
      return render(App, { props })
    },
  }),
)
```

:::

## 2. Hydrate on the client

When the page was rendered on the server, hydrate it instead of mounting a new app:

::: code-group

```jsx [React]
import { createRoot, hydrateRoot } from 'react-dom/client'

setup({ el, App, props }) {
  if (el.hasAttribute('data-server-rendered')) {
    hydrateRoot(el, <App {...props} />)
  } else {
    createRoot(el).render(<App {...props} />)
  }
},
```

```js [Vue]
import { createApp, createSSRApp, h } from 'vue'

setup({ el, App, props, plugin }) {
  const create = el.hasAttribute('data-server-rendered') ? createSSRApp : createApp
  create({ render: () => h(App, props) }).use(plugin).mount(el)
},
```

```js [Svelte]
import { hydrate, mount } from 'svelte'

setup({ el, App, props }) {
  if (el.hasAttribute('data-server-rendered')) {
    hydrate(App, { target: el, props })
  } else {
    mount(App, { target: el, props })
  }
},
```

:::

## 3. Build and start the SSR server

Build the entry point into its own directory, separate from the client build:

```json
{
  "scripts": {
    "build": "vite build && vite build --ssr src/ssr.jsx --outDir dist-ssr"
  }
}
```

Start the server (it listens on port 13714) and keep it running with your process manager:

```bash
node frontend/dist-ssr/ssr.js
```

## 4. Enable SSR in Yii

```php
'inertia' => [
    'ssrEnabled' => true,
    'ssrGateway' => [
        'class' => \Crenspire\Yii2Inertia\Ssr\HttpGateway::class,
        'url' => 'http://127.0.0.1:13714',
        // Optional: skip SSR (without contacting the server) when the bundle has not been built
        'bundle' => '@app/frontend/dist-ssr/ssr.js',
    ],
],
```

The root view already outputs the server-rendered head elements and body with `Inertia::ssrHead($ssr)` and
`Inertia::app($page, $ssr)`.

## Failures

A failing render never breaks the page. When the SSR server is unreachable or returns an error, the adapter logs the
error (including the hint and source location reported by the SSR server) and renders the page on the client.

Set `throwOnError` on the gateway to throw an `SsrException` instead, which is useful in end-to-end tests:

```php
'ssrGateway' => ['class' => HttpGateway::class, 'throwOnError' => YII_ENV_TEST],
```

Check whether the server is running with `Inertia::getManager()->getSsrGateway()->isHealthy()`.

## Controlling SSR

```php
'ssrEnabled' => fn (\yii\web\Request $request) => !str_starts_with($request->getUrl(), '/admin'),
'ssrExcept' => ['admin/*', 'account/*'],
```

```php
Inertia::disableSsr();                         // for the current request
Inertia::disableSsr(fn ($request) => ...);     // conditionally
Inertia::withoutSsr(['reports/*']);            // exclude paths
```

Only the first visit is server-side rendered; Inertia visits always return JSON.

## Development

When [`vite.devServerUrl`](/reference/vite#devserverurl) is configured, the gateway renders pages through the Vite dev
server's `/__inertia_ssr` endpoint, provided by the [`@inertiajs/vite`](https://www.npmjs.com/package/@inertiajs/vite)
plugin. See the Inertia documentation for setting up the plugin.

## Custom gateways

Implement `Crenspire\Yii2Inertia\Ssr\Gateway` to render pages another way:

```php
use Crenspire\Yii2Inertia\Ssr\Gateway;
use Crenspire\Yii2Inertia\Ssr\SsrResponse;

final class MyGateway implements Gateway
{
    public function dispatch(array $page): ?SsrResponse
    {
        // return null to fall back to client-side rendering
    }
}
```

```php
'ssrGateway' => ['class' => MyGateway::class],
```
