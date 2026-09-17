# Protocol handling

The adapter implements the server side of the [Inertia.js v3 protocol](https://inertiajs.com/docs/v3/core-concepts/the-protocol).
This page lists what it does for you, which helps when debugging requests in the browser dev tools.

## Responses

| Request | Response |
|---|---|
| Regular `GET` (first visit) | `200`, root view with `<script data-page="app" type="application/json">` |
| Inertia visit (`X-Inertia: true`) | `200`, JSON page object, headers `X-Inertia: true` and `Vary: X-Inertia` |

Every response carries `Vary: X-Inertia`, so caches never mix HTML and JSON for the same URL.

## Page object

| Property | When |
|---|---|
| `component`, `props`, `url`, `version` | always (`props.errors` always present) |
| `sharedProps` | shared props exist and `exposeSharedPropKeys` is enabled |
| `deferredProps` | deferred props on a full visit |
| `mergeProps`, `prependProps`, `deepMergeProps`, `matchPropsOn` | merge props are sent |
| `onceProps` | once props exist |
| `scrollProps` | scroll props are sent |
| `rescuedProps` | a deferred prop with `rescue` failed |
| `flash` | flash data exists |
| `encryptHistory`, `clearHistory`, `preserveFragment` | when `true` |

## Request headers

| Header | Handling |
|---|---|
| `X-Inertia` | Identifies Inertia visits |
| `X-Inertia-Version` | Compared with the current version on `GET` requests |
| `X-Inertia-Partial-Component` | Partial reload filters apply only when it matches the rendered component |
| `X-Inertia-Partial-Data` / `X-Inertia-Partial-Except` | `only` / `except` prop paths |
| `X-Inertia-Reset` | Props whose merge metadata is reset |
| `X-Inertia-Error-Bag` | Nests default-bag validation errors under the bag name |
| `X-Inertia-Except-Once-Props` | Once props the client already holds |
| `X-Inertia-Infinite-Scroll-Merge-Intent` | `append` or `prepend` for scroll props |
| `X-XSRF-TOKEN` | Copied to Yii's CSRF header before validation |
| `Purpose: prefetch` | Fragment redirects are not converted |

## Lifecycle

When the component is bootstrapped, it hooks into every request:

| Stage | Behaviour |
|---|---|
| Bootstrap | Registers `JsonParser` for `application/json` bodies |
| Before request | Accepts the CSRF token from `X-XSRF-TOKEN` |
| Before action | Outdated asset version on an Inertia `GET`: `409` with `X-Inertia-Location` and `X-Inertia-Version`, the action does not run |
| Before send | Adds `Vary: X-Inertia` |
| Before send (Inertia requests) | Restores `Location` for Yii AJAX redirects (`X-Redirect`); redirects back when the response is empty; `302` → `303` after `PUT`/`PATCH`/`DELETE`; redirects with a `#fragment` → `409` with `X-Inertia-Redirect` |
| Before send | Sends the `XSRF-TOKEN` cookie for Inertia requests and rendered pages |

## Encoding

Page objects are encoded with `JSON_HEX_TAG` (so user data cannot close the `<script>` element) and
`JSON_UNESCAPED_UNICODE`. Invalid UTF-8 throws a `JsonException`. Empty objects are encoded as `{}`, not `[]`.
