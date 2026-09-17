# Authentication

Authentication works as in any Yii application: `yii\web\User`, `AccessControl` filters and RBAC. This page covers the
Inertia-specific details.

## Sharing the current user

```php
'inertia' => [
    'shared' => [
        'auth.user' => fn () => Yii::$app->user->identity?->toArray(['id', 'name', 'email']),
    ],
],
```

::: danger
Never share the identity object directly: it would expose its password hash and auth key. See
[models and data exposure](/guide/responses#models-and-data-exposure).
:::

```jsx
import { usePage } from '@inertiajs/react'

export default function UserMenu() {
  const { auth } = usePage().props

  return auth.user ? <span>{auth.user.name}</span> : <a href="/login">Log in</a>
}
```

Authorization belongs on the server. Share the permissions the UI needs (`'auth.can' => fn () => [...]`) and keep
enforcing them in controllers.

## Protecting pages

`AccessControl` works for Inertia visits: guests are redirected to [`User::$loginUrl`](https://www.yiiframework.com/doc/api/2.0/yii-web-user#$loginUrl-detail)
and unauthorized users receive `403 Forbidden`.

```php
public function behaviors(): array
{
    return [
        'access' => [
            'class' => AccessControl::class,
            'rules' => [['allow' => true, 'roles' => ['@']]],
        ],
    ];
}
```

### Returning to the requested page after login

Inertia visits are AJAX requests, and Yii does not remember the requested URL for AJAX requests. To send users back to
the page they tried to open, store the return URL in a `denyCallback`:

```php
'access' => [
    'class' => AccessControl::class,
    'rules' => [['allow' => true, 'roles' => ['@']]],
    'denyCallback' => function () {
        $user = Yii::$app->user;
        if (!$user->getIsGuest()) {
            throw new \yii\web\ForbiddenHttpException('You are not allowed to access this page.');
        }
        // false: also remember the return URL for AJAX (Inertia) requests
        $user->loginRequired(false);
    },
],
```

## Logging in and out

```php
public function actionLogin(): Response
{
    $model = new LoginForm();

    if ($this->request->isPost) {
        if ($model->load($this->request->post(), '') && $model->login()) {
            return $this->goBack(['dashboard/index']);
        }

        Inertia::withErrors($model);

        return Inertia::back();
    }

    return Inertia::render('Auth/Login');
}

public function actionLogout(): Response
{
    Yii::$app->user->logout();
    Inertia::clearHistory();

    return $this->goHome();
}
```

`Inertia::clearHistory()` removes the encrypted history state, see [history encryption](/guide/history-encryption).

Use `Inertia::location()` instead of a redirect when the page after login must be a full page load, for example to
reload assets that differ for authenticated users.

The CSRF token keeps working across logins: the `XSRF-TOKEN` cookie is refreshed with every Inertia response.
