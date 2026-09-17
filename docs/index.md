---
layout: home

hero:
  name: Yii2 Inertia
  text: Modern single-page apps with classic Yii controllers
  tagline: The Inertia.js v3 adapter for Yii 2. Build React, Vue or Svelte frontends without writing an API.
  image:
    src: /logo.svg
    alt: Yii2 Inertia
  actions:
    - theme: brand
      text: Get started
      link: /guide/introduction
    - theme: alt
      text: Installation
      link: /guide/installation
    - theme: alt
      text: View on GitHub
      link: https://github.com/crenspire/yii2-inertia

features:
  - icon: 🚀
    title: Zero configuration
    details: The inertia component registers and bootstraps itself. Render your first page with one line in a controller.
  - icon: 📡
    title: Complete v3 protocol
    details: Partial reloads, deferred, optional, once, merge and infinite scroll props, flash data, error bags, history encryption and fragment redirects.
  - icon: 🧩
    title: Made for Yii
    details: Yii redirects, CSRF validation, JSON form bodies, validation errors from models and data providers work out of the box.
  - icon: ⚡
    title: Vite built in
    details: Asset tags from the build manifest or the dev server with hot module replacement, React Fast Refresh and CSP nonces.
  - icon: 🖥️
    title: Server-side rendering
    details: Render the first visit on the server through the Inertia SSR server or the Vite dev server, with automatic fallback.
  - icon: ✅
    title: Tested
    details: A test suite covering the protocol on PHP 8.1 to 8.5, verified against a real application with React 19.
---

## At a glance

```php
use Crenspire\Yii2Inertia\Inertia;

class UserController extends \yii\web\Controller
{
    public function actionIndex(): \yii\web\Response
    {
        return Inertia::render('Users/Index', [
            'users' => fn () => User::find()->select(['id', 'name', 'email'])->asArray()->all(),
        ]);
    }
}
```

```jsx
// src/pages/Users/Index.jsx
export default function Index({ users }) {
  return (
    <ul>
      {users.map((user) => <li key={user.id}>{user.name}</li>)}
    </ul>
  )
}
```

Continue with the [introduction](/guide/introduction) or jump straight to [installation](/guide/installation).
