# Yii2 Inertia.js — How-to Guide & Tips

Recipes and practical advice for building applications with `crenspire/yii2-inertia` 2.x and Inertia.js v3.
Installation, configuration and the API reference are in the [README](README.md).

## Contents

1. [Structuring pages](#structuring-pages)
2. [Authentication and shared data](#authentication-and-shared-data)
3. [Forms](#forms)
4. [File uploads](#file-uploads)
5. [Lists and pagination](#lists-and-pagination)
6. [Error pages](#error-pages)
7. [Performance](#performance)
8. [Testing](#testing)
9. [Troubleshooting](#troubleshooting)

---

## Structuring pages

Mirror your controllers in the page directory and resolve components with `import.meta.glob`:

```
src/pages/
  Home.jsx
  Users/
    Index.jsx
    Edit.jsx
```

```php
return Inertia::render('Users/Edit', ['user' => $user->toArray(['id', 'name', 'email'])]);
```

Use a persistent layout so the layout is not re-mounted between visits:

```jsx
// src/main.jsx
resolve: (name) => {
  const page = import.meta.glob('./pages/**/*.jsx', { eager: true })[`./pages/${name}.jsx`]
  page.default.layout ??= (content) => <Layout>{content}</Layout>
  return page
},
```

Set the document title from pages with `<Head title="Users" />` and a `title` callback in `createInertiaApp()`.
Give the root view's `<title>` a `data-inertia` attribute so it is managed by Inertia.

## Authentication and shared data

Share the current user from the component config. Always pick the attributes explicitly: everything in props is
visible in the page source.

```php
'inertia' => [
    'shared' => [
        'auth.user' => fn () => Yii::$app->user->identity?->toArray(['id', 'name', 'email']),
        'auth.can' => fn () => [
            'manageUsers' => Yii::$app->user->can('manageUsers'),
        ],
    ],
],
```

```jsx
import { usePage } from '@inertiajs/react'

export default function UserMenu() {
  const { auth } = usePage().props
  return auth.user ? <span>{auth.user.name}</span> : <a href="/login">Log in</a>
}
```

Authorization belongs on the server (access control filters, RBAC); client-side checks only hide UI.

Redirect to the login page as usual — `$this->redirect()` and Yii's `AccessControl` filter work for Inertia requests.
After logging out, clear the encrypted history if you use history encryption:

```php
public function actionLogout(): Response
{
    Yii::$app->user->logout();
    Inertia::clearHistory();

    return $this->goHome();
}
```

## Forms

### Validation errors

```php
public function actionUpdate(int $id): Response
{
    $user = $this->findUser($id);

    if (!$user->load(Yii::$app->request->post(), '') || !$user->save()) {
        Inertia::withErrors($user);

        return Inertia::back();
    }

    Inertia::flash('success', 'Profile updated.');

    return $this->redirect(['user/edit', 'id' => $user->id]);
}
```

Load the data with an empty form name (`load($data, '')`): Inertia sends the fields at the top level of the JSON body.

```jsx
import { useForm } from '@inertiajs/react'

export default function Edit({ user }) {
  const { data, setData, put, processing, errors } = useForm({ name: user.name, email: user.email })

  return (
    <form onSubmit={(e) => { e.preventDefault(); put(`/users/${user.id}`) }}>
      <input value={data.name} onChange={(e) => setData('name', e.target.value)} />
      {errors.name && <div className="error">{errors.name}</div>}
      <button disabled={processing}>Save</button>
    </form>
  )
}
```

Map the `PUT` route in the URL manager, e.g. `'PUT users/<id:\d+>' => 'user/update'`. The adapter turns the redirect
into a `303` so the browser follows it with `GET`.

### Several forms on one page

Scope errors with an error bag: `post('/login', { errorBag: 'login' })` and `Inertia::withErrors($form, 'login')`.
The errors are then available as `errors.login.email`.

### Showing flash messages

```jsx
const { flash } = usePage()
{flash.success && <div className="alert">{flash.success}</div>}
```

Or react to them once with `router.on('flash', (event) => toast(event.detail.flash))`.

## File uploads

Files are sent as `multipart/form-data`, which PHP only parses for `POST`. For updates, spoof the method:

```jsx
router.post(`/users/${user.id}/avatar`, { _method: 'put', avatar: file })
```

Yii reads `_method` (`Request::$methodParam`), so the action sees a `PUT` request and `UploadedFile::getInstanceByName('avatar')` works.

## Lists and pagination

### Infinite scroll

```php
return Inertia::render('Posts/Index', [
    'posts' => Inertia::scroll(fn () => new ActiveDataProvider([
        'query' => Post::find()->select(['id', 'title'])->asArray(),
        'pagination' => ['pageSize' => 20],
    ])),
]);
```

```jsx
<InfiniteScroll data="posts">
  {posts.data.map((post) => <PostCard key={post.id} post={post} />)}
</InfiniteScroll>
```

### Classic pagination

```php
$provider = new ActiveDataProvider(['query' => Post::find()->asArray(), 'pagination' => ['pageSize' => 20]]);

return Inertia::render('Posts/Index', [
    'posts' => $provider->getModels(),
    'pagination' => [
        'page' => $provider->getPagination()->getPage() + 1,
        'pageCount' => $provider->getPagination()->getPageCount(),
        'total' => $provider->getTotalCount(),
    ],
    'filters' => Yii::$app->request->get(),
]);
```

Navigate with `<Link href={`/posts?page=${page}`} preserveScroll>`; the query string is kept in the page URL.

## Error pages

Render errors as Inertia pages by pointing the error handler at an action that renders a component:

```php
// config/web.php
'errorHandler' => ['errorAction' => 'site/error'],
```

```php
public function actionError(): Response
{
    $exception = Yii::$app->errorHandler->exception;
    $status = $exception instanceof \yii\web\HttpException ? $exception->statusCode : 500;

    return Inertia::render('Error', ['status' => $status]);
}
```

Keep `YII_DEBUG` error pages in development; Inertia shows non-Inertia error responses in a modal.

## Performance

- **Send less data.** Select columns (`->select([...])->asArray()`) instead of passing full models.
- **Make expensive props lazy.** Closures are only evaluated when the prop is sent; `Inertia::optional()` props only when requested.
- **Defer slow props** with `Inertia::defer()` so the page renders immediately. Group props that should load together.
- **Reload only what changed** with `router.reload({ only: ['stats'] })`. Shared props are skipped too unless they are `Inertia::always()`.
- **Load reference data once** with `Inertia::once()` / `Inertia::shareOnce()` (countries, plans, permissions).
- **Cache** expensive shared data: `fn () => Yii::$app->cache->getOrSet('settings', fn () => Setting::all(), 3600)`.
- **Prefetch** likely navigations with `<Link prefetch>`.

## Testing

Simulate an Inertia visit by adding the `X-Inertia` header, then decode the page object:

```php
public function testIndexListsUsers(FunctionalTester $I): void
{
    $I->haveHttpHeader('X-Inertia', 'true');
    $I->amOnPage('/users');

    $page = json_decode($I->grabPageSource(), true);
    $I->assertSame('Users/Index', $page['component']);
    $I->assertCount(3, $page['props']['users']);
}
```

Without the header you get the HTML root view; the page object is in `<script data-page="app" type="application/json">`.
Send the `X-Inertia-Version` header with the current version (`Inertia::getVersion()`) to avoid `409` responses:
real clients always send it, and a missing or outdated version triggers a full reload.

When the test environment has no frontend build, let pages render without asset tags:

```php
// config/test.php
'components' => [
    'inertia' => ['vite' => ['throwOnMissingManifest' => false]],
],
```

## Troubleshooting

### The page is blank

- Open the browser console and network tab. If the JS/CSS files return 404/500, check `vite.buildPath` / `vite.baseUrl`
  and that `npm run build` wrote `.vite/manifest.json` into the build directory.
- With PHP's built-in server, use a router script that serves existing files (`examples/basic/web/router.php`);
  passing `index.php` as the router sends asset requests to Yii.
- Make sure the client adapter is Inertia **v3** — older clients read a `data-page` attribute that v3 no longer renders.

### Every visit is a full page reload

The asset version changes between requests. A closure passed to `Inertia::version()` must return a stable value; the
default (Vite manifest hash) only changes when you rebuild.

### "Unable to verify your data submission" (HTTP 400)

- Check that the `XSRF-TOKEN` cookie exists and the request carries the `X-XSRF-TOKEN` header.
- Cookies are shared by all ports of a host: another local app on `127.0.0.1` using an `XSRF-TOKEN` cookie (Laravel,
  Django, Angular) overwrites the token. Use a different host name (`localhost` vs `127.0.0.1`) or change
  `csrfCookieName` / `csrfHeaderName` and the client's `http` options (`xsrfCookieName`, `xsrfHeaderName`).
- If the request is not handled through the component's bootstrap (custom bootstrapping), add `'bootstrap' => ['inertia']`.

### Form data is empty in the controller

Inertia posts JSON. The adapter registers `yii\web\JsonParser` unless the request component already has a parser for
`application/json`. Load models with an empty form name: `$model->load(Yii::$app->request->post(), '')`.

### Redirects show an error modal or do not navigate

Return the redirect response (`return $this->redirect(...)`). Use `Inertia::location()` only for URLs outside the
Inertia app. Make sure the component is bootstrapped, which fixes Yii's `X-Redirect` header for AJAX requests.

### Props are missing on partial reloads

Partial reloads send only the requested props (and `Inertia::always()` props), including shared props. Request
nested values with dot notation: `only: ['auth.user']`.

### Server-side rendering does not happen

Check the application log for `Inertia SSR failed` messages, confirm the SSR server answers on `/health`
(`Yii::$app->inertia->getSsrGateway()->isHealthy()`), and remember that Inertia visits are always rendered on the client.
