# Configuration

The adapter is configured through the `inertia` application component, an instance of
`Crenspire\Yii2Inertia\Manager`. All properties are optional.

```php
// config/web.php
'components' => [
    'inertia' => [
        'class' => \Crenspire\Yii2Inertia\Manager::class,
        'rootView' => '@app/views/layouts/inertia.php',
        'version' => null,
        'shared' => [],
        'encryptHistory' => false,
        'withAllErrors' => false,
        'exposeSharedPropKeys' => true,
        'urlResolver' => null,
        'enableCsrfCookie' => true,
        'csrfCookieName' => 'XSRF-TOKEN',
        'csrfHeaderName' => 'X-XSRF-TOKEN',
        'registerJsonParser' => true,
        'ssrEnabled' => false,
        'ssrExcept' => [],
        'ssrGateway' => ['class' => \Crenspire\Yii2Inertia\Ssr\HttpGateway::class],
        'vite' => [],
    ],
],
```

The component is registered with its defaults when it is not configured, and bootstrapped automatically through the
package's `extra.bootstrap` entry.

## Pages

### rootView

`string`, default `'@app/views/layouts/inertia.php'`

The view file rendered on the first visit. It receives the `$page` array, the `$ssr` response (or `null`) and the view
data passed to `render()`. See [the root view](/guide/installation#create-the-root-view).

### urlResolver

`Closure|null`, default `null`

Computes the `url` of the page object. It receives the `yii\web\Request` and returns a string. By default the request URI
is used (`Request::getUrl()`, including the query string).

```php
'urlResolver' => fn (\yii\web\Request $request) => '/app' . $request->getUrl(),
```

## Props

### shared

`array`, default `[]`

Props shared with every page. Closures are evaluated lazily; keys with dots create nested props. See
[shared data](/guide/shared-data).

### exposeSharedPropKeys

`bool`, default `true`

Lists the top-level keys of the shared props in the page's `sharedProps`, which lets the client carry them over during
client-side visits.

### withAllErrors

`bool`, default `false`

Sends every validation message of an attribute as an array instead of only the first message. See
[validation errors](/guide/forms#validation-errors).

## Asset versioning

### version

`string|int|Closure|null`, default `null`

The asset version. `null` uses the hash of the Vite manifest. See [asset versioning](/guide/asset-versioning).

## History

### encryptHistory

`bool`, default `false`

Encrypts the browser history state of every page. See [history encryption](/guide/history-encryption).

## Requests

### enableCsrfCookie

`bool`, default `true`

Sends the CSRF token in a JavaScript-readable cookie and accepts it from the header the Inertia HTTP client sends. See
[CSRF protection](/guide/csrf-protection).

### csrfCookieName

`string`, default `'XSRF-TOKEN'`

Name of the CSRF cookie. Match it with the client's `http.xsrfCookieName` option.

### csrfHeaderName

`string`, default `'X-XSRF-TOKEN'`

Name of the header carrying the token from the client. Match it with the client's `http.xsrfHeaderName` option.

### registerJsonParser

`bool`, default `true`

Registers `yii\web\JsonParser` for `application/json` request bodies when the request component has no parser for it.

## Server-side rendering

### ssrEnabled

`bool|Closure`, default `false`

Enables server-side rendering of the first visit. A closure receives the request and returns a boolean. See
[server-side rendering](/guide/ssr).

### ssrExcept

`string[]`, default `[]`

URL path patterns (without a leading slash, `*` wildcards allowed) that are never server-side rendered.

### ssrGateway

`array|string|Gateway`, default `['class' => HttpGateway::class]`

The SSR gateway: a configuration array, a component ID or an object implementing `Crenspire\Yii2Inertia\Ssr\Gateway`.
See [SSR gateway](/reference/ssr).

## Vite

### vite

`array|Vite`, default `[]`

Configuration of the Vite helper. See [Vite helper](/reference/vite).
