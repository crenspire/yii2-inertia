# Pages and props

## Rendering a page

Return `Inertia::render()` from a controller action. The first argument is the page component name, the second the props
passed to it:

```php
use Crenspire\Yii2Inertia\Inertia;
use yii\web\Response;

public function actionView(int $id): Response
{
    $user = $this->findModel($id);

    return Inertia::render('Users/View', [
        'user' => $user->toArray(['id', 'name', 'email']),
        'canEdit' => Yii::$app->user->can('updateUser', ['user' => $user]),
    ]);
}
```

The global `inertia()` helper is a shortcut for the same call:

```php
return inertia('Users/View', ['user' => $user->toArray(['id', 'name', 'email'])]);
```

`Inertia::render()` returns the application's `yii\web\Response`: the root view (HTML) on the first visit and the page
object (JSON) on Inertia visits. Both are built from exactly the same props.

### Component names

Component names are resolved by your client-side `resolve` callback. By convention they mirror the page directory,
e.g. `Users/Index` resolves to `src/pages/Users/Index.jsx`.

String-backed enums are accepted too:

```php
enum Page: string
{
    case UsersIndex = 'Users/Index';
}

return Inertia::render(Page::UsersIndex, $props);
```

## Props

Props are encoded to JSON. The adapter prepares every value before encoding:

| Value | Sent as |
|---|---|
| Scalars, arrays, `null` | as is |
| `Closure` | its return value — evaluated only when the prop is sent |
| `yii\base\Arrayable` (models, active records) | the result of `toArray()` |
| `JsonSerializable` | the result of `jsonSerialize()` |
| [Prop types](/reference/props) (`Inertia::defer()`, ...) | resolved according to their rules |

Values are resolved recursively, so closures and models can be nested inside arrays.

### Lazy evaluation

Wrap expensive props in closures. They are only evaluated when the prop is actually included in the response, which
matters for [partial reloads](/guide/partial-reloads):

```php
return Inertia::render('Dashboard', [
    'stats' => fn () => Stats::summary(),
    'recentOrders' => fn () => Order::find()->latest()->limit(10)->asArray()->all(),
]);
```

### Models and data exposure

::: danger Everything in props is visible in the browser
Props are embedded in the page source and in JSON responses. Passing a model sends every field returned by its
`fields()` method. For an `ActiveRecord` identity that includes columns such as `password_hash` and `auth_key`.
:::

Select the attributes explicitly:

```php
'user' => $user->toArray(['id', 'name', 'email']),
'users' => User::find()->select(['id', 'name'])->asArray()->all(),
```

or define the public representation once by overriding `fields()`:

```php
class User extends ActiveRecord
{
    public function fields(): array
    {
        return ['id', 'name', 'email', 'createdAt' => 'created_at'];
    }
}
```

### Dot-notation keys

Keys containing dots create nested props:

```php
return Inertia::render('Profile', [
    'user.name' => $user->name,
    'user.avatar' => fn () => $user->getAvatarUrl(),
]);
// props: { user: { name: "...", avatar: "..." } }
```

### Property providers

A class implementing `ProvidesInertiaProperties` contributes several props at once. Add it without a key:

```php
use Crenspire\Yii2Inertia\Props\ProvidesInertiaProperties;
use Crenspire\Yii2Inertia\Props\RenderContext;

final class UserProps implements ProvidesInertiaProperties
{
    public function __construct(private User $user) {}

    public function toInertiaProperties(RenderContext $context): iterable
    {
        yield 'user' => $this->user->toArray(['id', 'name']);
        yield 'permissions' => fn () => $this->user->getPermissionNames();
    }
}

return Inertia::render('Users/Edit', [new UserProps($user), 'roles' => Role::list()]);
```

A class implementing `ProvidesInertiaProperty` computes a single value and receives a `PropertyContext` with the prop
path, its sibling props and the request.

## Root view data

The root view receives the `$page` and `$ssr` variables. Pass additional variables with the third argument:

```php
return Inertia::render('Blog/Post', ['post' => $post], ['title' => $post->title]);
```

```php
<title data-inertia><?= Html::encode($title ?? Yii::$app->name) ?></title>
```

View data is only used on the first visit; it is not part of the page object.

Change the root view globally with the [`rootView`](/reference/configuration#rootview) property or at runtime:

```php
Inertia::setRootView('@app/views/layouts/admin.php');
```
