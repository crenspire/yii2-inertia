# Partial reloads

A partial reload requests a subset of the props of the current page, for example to refresh a list after changing a
filter without re-evaluating everything else.

```js
import { router } from '@inertiajs/react'

router.reload({ only: ['users'] })
router.reload({ except: ['stats'] })
```

Links accept the same options: `<Link href="/users?active=1" only={['users']}>`.

## On the server

No changes are needed. The adapter reads the `X-Inertia-Partial-Data` (`only`) and `X-Inertia-Partial-Except` headers
and returns only the matching props. Props that are left out are **not evaluated**, as long as they are closures:

```php
return Inertia::render('Users/Index', [
    'users' => fn () => User::find()->filterWhere(['status' => $status])->asArray()->all(),
    'stats' => fn () => Stats::expensiveSummary(), // skipped by router.reload({ only: ['users'] })
]);
```

::: warning Use closures
A value computed before calling `render()` has already been computed, whether it is sent or not. Wrap expensive props
in closures to benefit from partial reloads.
:::

## Rules

- Partial reloads only apply when the requested component matches the rendered one. A partial reload that ends up on a
  different page (for example after a redirect) receives all props.
- `only` and `except` accept **dot-notation paths**: `only: ['auth.user']` returns just that nested value.
- **Shared props** are filtered like any other prop.
- `Inertia::always()` props and the `errors` prop are always included.
- [Optional props](#optional-props) and [deferred props](/guide/deferred-props) are only sent when requested.

## Optional props

`Inertia::optional()` props are never included in a normal visit and only evaluated when a partial reload asks for them:

```php
return Inertia::render('Users/Index', [
    'users' => fn () => $this->findUsers(),
    'exportPreview' => Inertia::optional(fn () => $this->buildExportPreview()),
]);
```

```js
router.reload({ only: ['exportPreview'] })
```

## Always props

`Inertia::always()` props are included in every response, even partial reloads that did not request them:

```php
'notifications' => Inertia::always(fn () => Notification::unreadCount()),
```
