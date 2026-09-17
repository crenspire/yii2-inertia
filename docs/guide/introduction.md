# Introduction

**Yii2 Inertia** (`crenspire/yii2-inertia`) is the server-side adapter for [Inertia.js](https://inertiajs.com) v3 in
the [Yii 2 framework](https://www.yiiframework.com).

Inertia lets you build a single-page application with React, Vue or Svelte while keeping the classic server-side
workflow: routing, controllers, authorization, validation and sessions stay in Yii. Instead of rendering HTML views,
your controllers return a page component name and its props, and the Inertia client renders the component in the
browser. There is no API to design, version or secure separately.

## How it works

1. **The first visit** is a normal browser request. Yii renders a *root view* — a small HTML document that loads your
   JavaScript bundle and contains the page data (component name and props) as JSON.
2. The Inertia client boots, reads the page data and renders the component.
3. **Every following visit** (clicking a `<Link>`, submitting a form) is an XHR request with an `X-Inertia` header.
   The same controller action runs, but this time the adapter responds with just the page data as JSON, and the
   client swaps the page component without a full reload.

```text
Browser                          Yii application
───────                          ───────────────
GET /users ────────────────────▶ UserController::actionIndex()
◀──────── HTML root view + <script data-page="app" type="application/json">

click <Link href="/users/1">
GET /users/1  (X-Inertia: true) ▶ UserController::actionView()
◀──────── { "component": "Users/View", "props": { ... }, "url": "/users/1", "version": "..." }
```

## What the adapter does

Rendering pages is only part of the protocol. The adapter also takes care of everything around it, for every request:

- **Props**: shared props, lazy closures, partial reloads, deferred / optional / once / merge / infinite scroll props,
  and conversion of models (`yii\base\Arrayable`) to arrays.
- **Asset versioning**: a full page reload when your frontend build changes.
- **Redirects**: Yii's `redirect()` works for Inertia visits, `303 See Other` after `PUT`/`PATCH`/`DELETE`, external
  and fragment redirects.
- **Forms**: JSON request bodies, validation errors (with error bags) and flash data for the next page.
- **Security**: CSRF protection through the cookie and header the Inertia HTTP client uses.
- **Tooling**: Vite asset tags (build manifest and dev server) and server-side rendering.

See [Protocol handling](/reference/protocol) for the complete list.

## Requirements

| | Version |
|---|---|
| PHP | 8.1 or newer |
| Yii | 2.0.55 or newer |
| Inertia.js client | 3.x (`@inertiajs/react`, `@inertiajs/vue3` or `@inertiajs/svelte`) |

::: tip Upgrading from 1.x?
Version 2.0 is a rewrite for the Inertia.js v3 protocol. Follow the [upgrade guide](/guide/upgrade).
:::

## Learn more

This documentation covers the Yii side of Inertia. For the client side — links, forms, the `usePage` hook, layouts,
prefetching, polling and more — see the official [Inertia.js documentation](https://inertiajs.com).
