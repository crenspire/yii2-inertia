# Asset versioning

When you deploy a new frontend build, users with an open tab still run the old JavaScript. Inertia detects this with
an asset version: every page object contains the current version, the client sends it back in the
`X-Inertia-Version` header, and when it no longer matches, the next visit becomes a full page load that picks up the
new assets.

## Default version

The default version is an MD5 hash of the Vite manifest (`@webroot/dist/.vite/manifest.json`, or
`@webroot/dist/manifest.json` for older Vite versions, see [`vite.buildPath`](/reference/vite#buildpath)). It changes
whenever you rebuild the assets. Without a manifest the version is an empty string.

## Custom version

Set the version when you use another build tool or deploy assets separately:

```php
// config/web.php
'inertia' => [
    'version' => fn () => md5_file(Yii::getAlias('@webroot/assets/manifest.json')),
],
```

or at runtime:

```php
Inertia::version('2026.09.17');
Inertia::version(fn () => getenv('ASSET_VERSION'));
Inertia::version(null); // back to the default
```

`Inertia::getVersion()` returns the current version as a string.

## How mismatches are handled

For an Inertia `GET` request with an outdated version, the adapter does not run the action. It responds with
`409 Conflict`, the current URL in `X-Inertia-Location` and the current version in `X-Inertia-Version`, and the client
performs a full page load of the same URL.

Non-`GET` requests are never interrupted, so form submissions are not lost; the redirect that follows them triggers the
reload.

::: tip
A version closure is evaluated for every Inertia request. Keep it cheap and stable — a value that changes between
requests causes a full reload on every visit.
:::
