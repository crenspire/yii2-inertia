# Troubleshooting

## The page is blank

- Open the browser console and the network tab. If JavaScript or CSS files fail to load, check
  [`vite.buildPath`](/reference/vite#buildpath) and [`vite.baseUrl`](/reference/vite#baseurl), and that `vite build`
  wrote `.vite/manifest.json` into the build directory.
- With PHP's built-in server, use a router script that serves existing files directly. Passing `index.php` as the router
  sends asset requests to Yii:

  ```php
  // web/router.php — php -S localhost:8080 -t web web/router.php
  $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
  if ($path !== '/' && is_file(__DIR__ . $path)) {
      return false;
  }
  require __DIR__ . '/index.php';
  ```

  `php yii serve` already handles static files.
- Make sure you use an Inertia **v3** client. Older clients read a `data-page` attribute that v3 no longer renders.

## "Vite manifest not found"

The frontend has not been built for the configured `buildPath`. Run `vite build`, configure
[`devServerUrl`](/reference/vite#devserverurl) in development, or set
[`throwOnMissingManifest`](/reference/vite#throwonmissingmanifest) to `false` in test environments.

## Every visit is a full page reload

The [asset version](/guide/asset-versioning) changes between requests. A closure passed to `version` must return a
stable value. Check the `X-Inertia-Version` request header against `Inertia::getVersion()`.

## Functional tests receive 409 Conflict

Tests that send `X-Inertia: true` must also send `X-Inertia-Version` with the current version. See
[testing](/guide/testing).

## "Unable to verify your data submission" (HTTP 400)

- Check that the `XSRF-TOKEN` cookie exists and requests carry the `X-XSRF-TOKEN` header (browser dev tools).
- **Another local application uses the same cookie.** Cookies are shared by all ports of a host, so a Laravel, Django or
  Angular app on `127.0.0.1` can overwrite the token. Use a different host name (`localhost` instead of `127.0.0.1`) or
  [rename the cookie](/guide/csrf-protection#custom-cookie-and-header-names).
- Make sure the component is bootstrapped (see [installation](/guide/installation)): the header is accepted during bootstrap.

## Form data is empty in the controller

Inertia sends JSON. The adapter registers `yii\web\JsonParser` unless the request component already defines a parser for
`application/json`. Load models with an empty form name: `$model->load(Yii::$app->request->post(), '')`.

## A redirect opens an error modal or does not navigate

- Return the redirect: `return $this->redirect(...)`.
- Use `Inertia::location()` only for URLs outside the Inertia application.
- Check that the component is bootstrapped; it restores the `Location` header that Yii removes for AJAX requests.

## Users are not sent back to the requested page after login

Yii does not store the return URL for AJAX requests. See
[returning to the requested page](/guide/authentication#returning-to-the-requested-page-after-login).

## Props are missing after a partial reload

Partial reloads only return the requested props, including shared props, plus `Inertia::always()` props. Request nested
values with dot notation (`only: ['auth.user']`).

## The page URL contains the query string twice

This was a bug in version 1.x. Upgrade to 2.0 and remove any workaround that rewrites `$page['url']`.

## Server-side rendering does not happen

- Only the first visit is server-side rendered.
- Look for `Inertia SSR failed` messages in the application log.
- Check that the SSR server runs: `Inertia::getManager()->getSsrGateway()->isHealthy()`.
- If you configured `bundle`, check that the file exists.

## Still stuck?

Search or open an issue on [GitHub](https://github.com/crenspire/yii2-inertia/issues).
