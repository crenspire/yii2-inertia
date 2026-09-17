# Upgrade guide

## From 1.x to 2.0

2.0 is a rewrite that implements the Inertia.js **v3** protocol. Upgrade your client adapter at the same time
(`@inertiajs/react`, `@inertiajs/vue3` or `@inertiajs/svelte` `^3.0`); v1 and v2 clients are not supported.

### Requirements

- Yii `~2.0.55` (all earlier releases are affected by published security advisories).
- PHP 8.1+ (unchanged).

### Root view

The page data is no longer a `data-page` attribute. Replace the root element in `views/layouts/inertia.php`:

```diff
- <div id="app" data-page="<?= htmlspecialchars(json_encode($page), ENT_QUOTES, 'UTF-8') ?>"></div>
+ <?= Inertia::app($page, $ssr) ?>
```

Replace hard-coded asset URLs (which never matched Vite's hashed file names) with the Vite helper:

```diff
- <script type="module" crossorigin src="/dist/assets/index.js"></script>
- <link rel="stylesheet" crossorigin href="/dist/assets/index.css">
+ <?= Inertia::vite()->tags('src/main.jsx') ?>
```

See [`stubs/inertia.php`](stubs/inertia.php) for a complete root view.

### Configuration

- `config/inertia.php` was removed (it was never loaded). Configure the `inertia` application component instead:

  ```php
  'components' => [
      'inertia' => [
          'class' => \Crenspire\Yii2Inertia\Manager::class,
          'rootView' => '@app/views/layouts/inertia.php',
          'shared' => [...],
      ],
  ],
  ```

- The component bootstraps itself. Asset version checks, `Vary` headers, redirect handling and CSRF support now run
  for every request instead of inside `Inertia::render()`.
- `Crenspire\Yii2Inertia\ViewRenderer` was removed. It could not work (the class did not compile, and Yii selects
  renderers by file extension). Use `Inertia::render()` in controllers.
- `Crenspire\Yii2Inertia\InertiaResponse` was removed; use `Inertia::render()` / `Inertia::location()`.

### API changes

| 1.x | 2.0 |
|---|---|
| `Inertia::version()` (getter) | `Inertia::getVersion()` — always returns a string |
| `Inertia::version($v)` | unchanged; `Inertia::version(null)` now restores the default |
| `Crenspire\Yii2Inertia\inertia()` | global `inertia()` function |
| Default version: `filemtime(@webroot/dist/manifest.json)` or `'1'` | `md5` of `@webroot/dist/.vite/manifest.json` (or `manifest.json`), or `''` |

### Behaviour changes

- **Page URL:** the query string is no longer duplicated (`/users?page=2?page=2`).
- **JSON responses:** `Inertia::render()` sets `$response->data` to the encoded JSON string (format `raw`) instead of an
  array. Tests should `json_decode()` it. Props are encoded identically on the first visit and on Inertia visits.
- **Partial reloads** only apply when `X-Inertia-Partial-Component` matches the rendered component, and they filter
  shared props too. Use `Inertia::always()` for shared props that must always be sent. `X-Inertia-Partial-Except` and
  dot-notation paths are supported.
- **Closures in props** are evaluated (previously they were encoded as `{}`), and only when the prop is sent.
- **Version mismatches** return `409` only for `GET` requests, with an absolute `X-Inertia-Location`. Requests
  without an `X-Inertia-Version` header are treated as outdated, as the protocol requires: functional tests that
  send `X-Inertia: true` must also send `X-Inertia-Version: <Inertia::getVersion()>`.
- **`Inertia::location()`** accepts routes (`['site/index']`) and no longer adds `X-Inertia-Location` to non-Inertia redirects.
- **Invalid UTF-8** in props throws a `JsonException` instead of rendering an empty page.
- `Vary: X-Inertia` is sent instead of `Vary: Accept`.

### New features

Validation errors (`withErrors()`), flash data, error bags, deferred / optional / once / merge / infinite scroll props,
history encryption, fragment redirects, CSRF cookie support, JSON request bodies, Vite integration and SSR.
See the [README](README.md).
