# Forms and validation

## Submitting forms

Inertia submits forms with XHR. Use `useForm` or the `<Form>` component on the client:

::: code-group

```jsx [React: useForm]
import { useForm } from '@inertiajs/react'

export default function Create() {
  const { data, setData, post, processing, errors } = useForm({ name: '', email: '' })

  const submit = (event) => {
    event.preventDefault()
    post('/users')
  }

  return (
    <form onSubmit={submit}>
      <input value={data.name} onChange={(e) => setData('name', e.target.value)} />
      {errors.name && <div>{errors.name}</div>}
      <input value={data.email} onChange={(e) => setData('email', e.target.value)} />
      {errors.email && <div>{errors.email}</div>}
      <button type="submit" disabled={processing}>Create</button>
    </form>
  )
}
```

```jsx [React: Form component]
import { Form } from '@inertiajs/react'

export default function Create() {
  return (
    <Form action="/users" method="post">
      {({ errors, processing }) => (
        <>
          <input name="name" />
          {errors.name && <div>{errors.name}</div>}
          <input name="email" />
          {errors.email && <div>{errors.email}</div>}
          <button type="submit" disabled={processing}>Create</button>
        </>
      )}
    </Form>
  )
}
```

:::

Vue and Svelte provide the same `useForm` helper and `Form` component, see the
[Inertia forms documentation](https://inertiajs.com/forms).

## Reading the submitted data

Form data is sent as JSON (or `multipart/form-data` when it contains files). The adapter registers `yii\web\JsonParser`
for `application/json` requests, so `Yii::$app->request->post()` returns the fields as usual.

The fields are sent at the top level, not wrapped in a form name, so load models with an empty form name:

```php
$model->load(Yii::$app->request->post(), '');
```

::: tip
If your request component already defines a parser for `application/json`, it is kept. Disable the automatic
registration with [`registerJsonParser`](/reference/configuration#registerjsonparser).
:::

## Validation errors

When validation fails, flash the errors with `Inertia::withErrors()` and redirect back. The errors are available in
the `errors` prop of the next page, which is what `useForm` and `<Form>` read:

```php
use Crenspire\Yii2Inertia\Inertia;

public function actionCreate(): Response
{
    $model = new User();

    if ($this->request->isPost) {
        if ($model->load($this->request->post(), '') && $model->save()) {
            Inertia::flash('success', 'User created.');

            return $this->redirect(['user/index']);
        }

        Inertia::withErrors($model);

        return Inertia::back();
    }

    return Inertia::render('Users/Create');
}
```

`withErrors()` accepts a model (all its errors) or an array of `attribute => message` or `attribute => [messages]`:

```php
Inertia::withErrors(['email' => 'This address is already registered.']);
```

By default the client receives the first message of each attribute:

```json
{ "errors": { "name": "Name cannot be blank.", "email": "Email is not a valid email address." } }
```

Enable [`withAllErrors`](/reference/configuration#withallerrors) to send every message as an array.

### Rendering instead of redirecting

Returning the form page directly with an `errors` prop works too. The redirect-back pattern is recommended because it
keeps the URL, browser history and page state consistent with a normal form submission.

```php
return Inertia::render('Users/Create', ['errors' => (object) $model->getFirstErrors()]);
```

### Error bags

When a page contains several forms, scope the errors of each form with an error bag. Pass the bag name on the client:

```jsx
post('/login', { errorBag: 'login' })
```

and either flash the errors to the default bag (the adapter nests them under the requested bag name)...

```php
Inertia::withErrors($model);
// errors: { login: { email: "..." } }
```

...or flash them to a named bag explicitly:

```php
Inertia::withErrors($model, 'login');
```

## Method spoofing

`PUT`, `PATCH` and `DELETE` requests work with Inertia's `put()`, `patch()` and `delete()` helpers. Remember to declare
the verbs in your URL rules or `VerbFilter`:

```php
'rules' => [
    'PUT users/<id:\d+>' => 'user/update',
    'DELETE users/<id:\d+>' => 'user/delete',
],
```

For uploads with these methods, see [file uploads](/guide/file-uploads).
