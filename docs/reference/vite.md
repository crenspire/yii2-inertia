# Vite helper

`Crenspire\Yii2Inertia\Vite` renders asset tags for Vite entry points. Access it with `Inertia::vite()` and configure it
through the `vite` property of the `inertia` component. See the [Vite guide](/guide/vite).

## Properties

### buildPath

`string`, default `'@webroot/dist'`

Directory Vite builds into (`build.outDir`).

### baseUrl

`string`, default `'@web/dist'`

Public URL of `buildPath`.

### manifestFiles

`string[]`, default `['.vite/manifest.json', 'manifest.json']`

Manifest locations relative to `buildPath`, checked in order. Vite 5 and newer write `.vite/manifest.json`.

### devServerUrl

`string|null`, default `null`

URL of the Vite dev server. When set, entry points are loaded from the dev server with hot module replacement.

### reactRefresh

`bool`, default `false`

Injects the React Fast Refresh preamble in dev server mode (required by `@vitejs/plugin-react`).

### throwOnMissingManifest

`bool`, default `true`

Whether `tags()` throws when the build manifest is missing. When `false`, it logs a warning and renders nothing.

## Methods

### tags()

```php
tags(string|array $entries): string
```

Returns the HTML tags for one or more entry points (as passed to `build.rollupOptions.input`, relative to the Vite
root). Throws `InvalidArgumentException` for entries missing from the manifest.

### asset()

```php
asset(string $path): string
```

Returns the public URL of a file processed by Vite.

### manifestHash()

```php
manifestHash(): ?string
```

Returns the MD5 hash of the manifest, or `null` when there is none. Used as the default asset version.

### isRunningHot()

```php
isRunningHot(): bool
```

Whether a dev server URL is configured.
