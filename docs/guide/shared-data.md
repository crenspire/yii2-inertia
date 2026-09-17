# Shared data

Shared props are added to every page, typically the authenticated user, flash messages or application settings.

## In the configuration

```php
// config/web.php
'components' => [
    'inertia' => [
        'shared' => [
            'appName' => 'My Application',
            'auth.user' => fn () => Yii::$app->user->identity?->toArray(['id', 'name', 'email']),
            'locale' => fn () => Yii::$app->language,
        ],
    ],
],
```

## At runtime

Share props from a bootstrap class, a base controller or a filter:

```php
use Crenspire\Yii2Inertia\Inertia;

Inertia::share('locale', Yii::$app->language);

Inertia::share([
    'auth.can' => fn () => [
        'manageUsers' => Yii::$app->user->can('manageUsers'),
    ],
]);
```

A bootstrap class is a convenient place for application-wide props:

```php
namespace app\components;

use Crenspire\Yii2Inertia\Inertia;
use Yii;
use yii\base\BootstrapInterface;

class InertiaBootstrap implements BootstrapInterface
{
    public function bootstrap($app): void
    {
        Inertia::share([
            'auth.user' => static fn () => Yii::$app->user->identity?->toArray(['id', 'name', 'email']),
        ]);
    }
}
```

```php
// config/web.php
'bootstrap' => ['log', \app\components\InertiaBootstrap::class],
```

Use closures for anything that depends on the request or the user: they are evaluated when the page is rendered, not when
the prop is shared.

Props passed to `Inertia::render()` override shared props with the same key.

## Reading and clearing shared props

```php
Inertia::getShared();                      // all shared props (unevaluated)
Inertia::getShared('auth.user');           // one prop, dot notation supported
Inertia::getShared('missing', 'default');  // with a default value
Inertia::flushShared();                    // remove all shared props
```

## The `errors` prop

Every page has an `errors` prop containing the [validation errors](/guide/forms) flashed with `Inertia::withErrors()`,
or an empty object. It is always included, even in partial reloads.

## Shared props on the client

The page object lists the shared prop keys in `sharedProps`, which lets the client carry them over during client-side
visits. Disable it with [`exposeSharedPropKeys`](/reference/configuration#exposesharedpropkeys).

Shared props follow the same rules as page props: they are skipped by [partial reloads](/guide/partial-reloads) that do
not request them. Wrap a shared prop in `Inertia::always()` when it must be included in every response:

```php
Inertia::share('notifications', Inertia::always(fn () => Notification::unreadCount()));
```

Shared props that rarely change can be sent once and remembered by the client, see [once props](/guide/once-props):

```php
Inertia::shareOnce('countries', fn () => Country::find()->select(['code', 'name'])->asArray()->all());
```
