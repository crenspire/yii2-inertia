# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - Unreleased

A rewrite implementing the Inertia.js v3 protocol. See the [upgrade guide](https://crenspire.github.io/yii2-inertia/guide/upgrade) for migration instructions.

### Added
- `inertia` application component (`Manager`), bootstrapped automatically through composer `extra.bootstrap`
- Inertia v3 page object: `sharedProps`, `deferredProps`, `mergeProps`, `prependProps`, `deepMergeProps`,
  `matchPropsOn`, `onceProps`, `scrollProps`, `rescuedProps`, `flash`, `encryptHistory`, `clearHistory`, `preserveFragment`
- Prop types: `Inertia::optional()`, `defer()` (with groups and rescue), `always()`, `merge()` / `deepMerge()`
  (with `prepend()`, `append()` and `matchOn()`), `once()`, `scroll()`, plus `ProvidesInertiaProperties` / `ProvidesInertiaProperty`
- Recursive prop resolution with nested special props and dot-notation partial reloads (`only` and `except`)
- `Inertia::withErrors()` with error bags, `Inertia::flash()`, `Inertia::back()`, `Inertia::shareOnce()`
- History encryption (`encryptHistory()`, `clearHistory()`) and `preserveFragment()`
- Request lifecycle handling: asset version checks (GET only), `Vary: X-Inertia`, `303` after PUT/PATCH/DELETE,
  fragment redirects via `X-Inertia-Redirect`, redirect back for empty responses, Yii AJAX redirect (`X-Redirect`) fix
- CSRF protection for the Inertia HTTP client (`XSRF-TOKEN` cookie / `X-XSRF-TOKEN` header)
- Automatic `JsonParser` registration for JSON form submissions
- `Vite` helper for manifest and dev server (HMR, React refresh, CSP nonces), with `throwOnMissingManifest`
  for test environments without a frontend build
- Server-side rendering through `Ssr\HttpGateway` (production server and Vite dev server), with path exclusions
- `Inertia::app()` and `Inertia::ssrHead()` root view helpers, `stubs/inertia.php`
- Documentation site at https://crenspire.github.io/yii2-inertia/ (guides and API reference)
- Example application rewritten for Inertia v3, React 19, Vite 8 and Tailwind CSS 4
- Test suite covering the protocol, lifecycle, Vite and SSR; CI on PHP 8.1–8.5 with lowest and highest dependencies

### Changed
- Requires `yiisoft/yii2` `~2.0.55`
- Initial page data is embedded in a `<script type="application/json">` element
- `Inertia::version()` is a setter only; use `Inertia::getVersion()` (always a string)
- Default asset version is the hash of the Vite manifest
- The `inertia()` helper is now a global function
- Inertia JSON responses are encoded by the adapter (`$response->data` is a string)

### Removed
- `ViewRenderer` (did not compile and could not be selected by Yii's renderer lookup)
- `InertiaResponse`
- `config/inertia.php` (was never loaded)

### Fixed
- Query string duplicated in the page URL
- `TypeError` when the version callback returned an integer
- Props serialized differently on the first visit and on Inertia visits; closures encoded as `{}`
- Partial reloads ignoring the component name
- Asset version mismatches returning `409` for non-GET requests
- Composer installation and CI (missing asset-packagist repository, plugin permissions, test bootstrap, lint step that could not fail)

## 1.0.0

- Initial release
