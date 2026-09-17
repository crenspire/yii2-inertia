# SSR gateway

The SSR gateway renders the first visit on the server. Configure it with the `ssrGateway` property of the `inertia`
component. See the [server-side rendering guide](/guide/ssr).

## HttpGateway

`Crenspire\Yii2Inertia\Ssr\HttpGateway` posts the page object to the Inertia SSR server.

### Properties

| Property | Type | Default | Description |
|---|---|---|---|
| `url` | `string` | `'http://127.0.0.1:13714'` | Base URL of the production SSR server (`POST /render`, `GET /health`) |
| `devServerUrl` | `string\|null` | `null` | Vite dev server URL; pages are rendered through its `/__inertia_ssr` endpoint. Filled in from `vite.devServerUrl` when not set |
| `bundle` | `string\|null` | `null` | Path or alias of the SSR bundle; SSR is skipped when the file is missing (unless the dev server is used) |
| `timeout` | `float` | `5.0` | Seconds to wait for the server |
| `throwOnError` | `bool` | `false` | Throw an `SsrException` instead of logging failures |

### Methods

```php
dispatch(array $page): ?SsrResponse
isHealthy(): bool
```

`dispatch()` returns `null` when rendering is skipped or fails, which falls back to client-side rendering.

## Gateway interface

```php
namespace Crenspire\Yii2Inertia\Ssr;

interface Gateway
{
    public function dispatch(array $page): ?SsrResponse;
}
```

## SsrResponse

```php
final class SsrResponse
{
    public readonly string $head;  // HTML for the <head>
    public readonly string $body;  // HTML of the application, including the page data
}
```

## SsrException

Thrown by `HttpGateway` when `throwOnError` is enabled. `$exception->details` contains the error reported by the SSR
server (`error`, `type`, `hint`, `sourceLocation`, ...).
