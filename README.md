<p align="center">
  <img src="docs/public/logo.svg" width="96" height="96" alt="Yii2 Inertia">
</p>

<h1 align="center">Yii2 Inertia</h1>

<p align="center">
  The <a href="https://inertiajs.com">Inertia.js</a> v3 adapter for the <a href="https://www.yiiframework.com">Yii 2</a> framework.<br>
  Build React, Vue and Svelte single-page apps with classic Yii controllers — no API required.
</p>

<p align="center">
  <a href="https://github.com/crenspire/yii2-inertia/actions/workflows/ci.yml"><img src="https://github.com/crenspire/yii2-inertia/actions/workflows/ci.yml/badge.svg" alt="CI"></a>
  <a href="https://packagist.org/packages/crenspire/yii2-inertia"><img src="https://img.shields.io/packagist/v/crenspire/yii2-inertia" alt="Latest version"></a>
  <a href="https://packagist.org/packages/crenspire/yii2-inertia"><img src="https://img.shields.io/packagist/dt/crenspire/yii2-inertia" alt="Downloads"></a>
  <a href="https://packagist.org/packages/crenspire/yii2-inertia"><img src="https://img.shields.io/packagist/dependency-v/crenspire/yii2-inertia/php" alt="PHP version"></a>
  <a href="LICENSE"><img src="https://img.shields.io/packagist/l/crenspire/yii2-inertia" alt="License"></a>
</p>

<p align="center">
  <a href="https://crenspire.github.io/yii2-inertia/"><strong>Documentation</strong></a> ·
  <a href="https://crenspire.github.io/yii2-inertia/guide/installation">Installation</a> ·
  <a href="https://crenspire.github.io/yii2-inertia/guide/upgrade">Upgrading from 1.x</a> ·
  <a href="CHANGELOG.md">Changelog</a>
</p>

---

## Features

- **Zero configuration** — the `inertia` component registers and bootstraps itself
- **Complete Inertia v3 protocol** — partial reloads, deferred, optional, once, merge and infinite scroll props, asset
  versioning, flash data, error bags, history encryption and fragment redirects
- **Made for Yii** — Yii redirects, CSRF validation, JSON form bodies, model validation errors and data providers work
  out of the box
- **Vite integration** — asset tags from the build manifest or the dev server with hot module replacement
- **Server-side rendering** with automatic fallback to client-side rendering

## Requirements

- PHP 8.1+
- Yii 2.0.55+
- An Inertia.js v3 client: `@inertiajs/react`, `@inertiajs/vue3` or `@inertiajs/svelte`

## Installation

```bash
composer require crenspire/yii2-inertia:^2.0
```

> Until 2.0.0 is tagged, install the development version: `composer require crenspire/yii2-inertia:2.0.x-dev`.

Create the root view in `views/layouts/inertia.php` (see [`stubs/inertia.php`](stubs/inertia.php)):

```php
<?php

use Crenspire\Yii2Inertia\Inertia;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var array $page */
/** @var Crenspire\Yii2Inertia\Ssr\SsrResponse|null $ssr */

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= Html::encode(Yii::$app->language) ?>">
<head>
    <meta charset="<?= Html::encode(Yii::$app->charset) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title data-inertia><?= Html::encode(Yii::$app->name) ?></title>
    <?= Inertia::vite()->tags('src/main.jsx') ?>
    <?= Inertia::ssrHead($ssr) ?>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
<?= Inertia::app($page, $ssr) ?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
```

Then set up the client side with Vite. The [installation guide](https://crenspire.github.io/yii2-inertia/guide/installation)
walks through React, Vue and Svelte.

## Usage

```php
use Crenspire\Yii2Inertia\Inertia;
use yii\web\Controller;
use yii\web\Response;

class UserController extends Controller
{
    public function actionIndex(): Response
    {
        return Inertia::render('Users/Index', [
            'users' => fn () => User::find()->select(['id', 'name', 'email'])->asArray()->all(),
            'stats' => Inertia::defer(fn () => Stats::summary()),
        ]);
    }

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
}
```

```jsx
// src/pages/Users/Index.jsx
import { Deferred, Link } from '@inertiajs/react'

export default function Index({ users, stats }) {
  return (
    <>
      <Deferred data="stats" fallback={<p>Loading stats…</p>}>
        <p>{stats?.total} users</p>
      </Deferred>
      <ul>
        {users.map((user) => (
          <li key={user.id}><Link href={`/users/${user.id}`}>{user.name}</Link></li>
        ))}
      </ul>
    </>
  )
}
```

## Documentation

The full documentation is available at **https://crenspire.github.io/yii2-inertia/**:

- [Introduction](https://crenspire.github.io/yii2-inertia/guide/introduction) and [installation](https://crenspire.github.io/yii2-inertia/guide/installation)
- [Pages and props](https://crenspire.github.io/yii2-inertia/guide/responses), [shared data](https://crenspire.github.io/yii2-inertia/guide/shared-data), [redirects](https://crenspire.github.io/yii2-inertia/guide/redirects), [forms and validation](https://crenspire.github.io/yii2-inertia/guide/forms), [CSRF protection](https://crenspire.github.io/yii2-inertia/guide/csrf-protection)
- [Partial reloads](https://crenspire.github.io/yii2-inertia/guide/partial-reloads), [deferred props](https://crenspire.github.io/yii2-inertia/guide/deferred-props), [merging props](https://crenspire.github.io/yii2-inertia/guide/merging-props), [once props](https://crenspire.github.io/yii2-inertia/guide/once-props), [infinite scroll](https://crenspire.github.io/yii2-inertia/guide/infinite-scroll)
- [Vite](https://crenspire.github.io/yii2-inertia/guide/vite), [server-side rendering](https://crenspire.github.io/yii2-inertia/guide/ssr), [testing](https://crenspire.github.io/yii2-inertia/guide/testing), [troubleshooting](https://crenspire.github.io/yii2-inertia/guide/troubleshooting)
- [Configuration](https://crenspire.github.io/yii2-inertia/reference/configuration) and [API reference](https://crenspire.github.io/yii2-inertia/reference/inertia)

## Example application

[`examples/basic`](examples/basic) is a complete application with React 19, Vite and Tailwind CSS, demonstrating
deferred props, partial reloads, forms with validation errors, flash data and infinite scroll:

```bash
cd examples/basic
composer install
cd vite && npm install && npm run build && cd ..
php -S localhost:8080 -t web web/router.php
```

## Contributing

Contributions are welcome. See [CONTRIBUTING.md](CONTRIBUTING.md) for the development setup, including how to run the
tests and the documentation site locally.

## License

The MIT License. See [LICENSE](LICENSE).
