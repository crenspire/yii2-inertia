# Yii2 Inertia.js Adapter

[![CI](https://github.com/crenspire/yii2-inertia/workflows/CI/badge.svg)](https://github.com/crenspire/yii2-inertia/actions)

An Inertia.js adapter for Yii2 framework, providing a seamless bridge between your Yii2 backend and modern JavaScript frontend frameworks (React, Vue, Svelte).

## Features

- 🚀 **Simple API**: Match the developer experience of `inertia-laravel`
- 📦 **Shared Props**: Share data across all Inertia responses
- 🔄 **Partial Reloads**: Support for partial page updates
- 🎯 **Asset Versioning**: Automatic version management for cache busting
- 🧪 **Well Tested**: Comprehensive unit and integration tests
- 📚 **Full Documentation**: Complete usage examples and guides

## Installation

Install via Composer:

```bash
composer require crenspire/yii2-inertia
```

## Quick Start

### 1. Configure Your Application

In your `config/web.php`, register the Inertia view renderer:

```php
'view' => [
    'renderers' => [
        'inertia' => \Crenspire\Yii2Inertia\ViewRenderer::class,
    ],
],
```

### 2. Create Root View Template

Create a root view template at `views/layouts/inertia.php`:

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inertia.js App</title>
    <script type="module" crossorigin src="/dist/assets/index.js"></script>
    <link rel="stylesheet" crossorigin href="/dist/assets/index.css">
</head>
<body>
    <div id="app" data-page="<?= htmlspecialchars(json_encode($page), ENT_QUOTES, 'UTF-8') ?>"></div>
</body>
</html>
```

### 3. Use in Controllers

```php
use Crenspire\Yii2Inertia\Inertia;

class HomeController extends \yii\web\Controller
{
    public function actionIndex()
    {
        return Inertia::render('Home', [
            'title' => 'Welcome',
            'user' => Yii::$app->user->identity,
        ]);
    }
}
```

### 4. Setup Frontend

Install Inertia.js and your frontend framework:

```bash
npm install @inertiajs/inertia @inertiajs/inertia-react react react-dom
```

Create `src/main.jsx`:

```jsx
import React from 'react';
import ReactDOM from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/inertia-react';
import Home from './pages/Home';

createInertiaApp({
  resolve: (name) => {
    const pages = { Home };
    return pages[name];
  },
  setup({ el, App, props }) {
    ReactDOM.createRoot(el).render(<App {...props} />);
  },
});
```

## API Reference

### Inertia::render()

Render an Inertia page:

```php
return Inertia::render('Dashboard', [
    'users' => User::find()->all(),
]);
```

### Inertia::share()

Share data with all Inertia responses:

```php
// Single key-value
Inertia::share('appName', 'My App');

// Multiple values
Inertia::share([
    'user' => Yii::$app->user->identity,
    'flash' => Yii::$app->session->getFlash('message'),
]);

// Using closures
Inertia::share('timestamp', function () {
    return time();
});
```

### Inertia::version()

Set or get the asset version:

```php
// String version
Inertia::version('1.0.0');

// Callback version
Inertia::version(function () {
    return filemtime(Yii::getAlias('@webroot/dist/manifest.json'));
});

// Get current version
$version = Inertia::version();
```

### Inertia::location()

Create an Inertia redirect response:

```php
return Inertia::location('/dashboard');
```

### Global Helper

You can also use the global `inertia()` helper function:

```php
return inertia('Home', ['title' => 'Welcome']);
```

## Partial Reloads

Inertia supports partial reloads for better performance. The client can request only specific props:

```php
// Client sends: X-Inertia-Partial-Component: Dashboard
// Client sends: X-Inertia-Partial-Data: users,stats

// Only 'users' and 'stats' props will be returned (plus shared props)
return Inertia::render('Dashboard', [
    'users' => $users,
    'stats' => $stats,
    'other' => $other, // This will be excluded
]);
```

## Redirects

For POST/PUT/PATCH/DELETE requests, Inertia handles redirects automatically:

```php
public function actionStore()
{
    // ... save data
    
    // For Inertia requests, returns 409 with X-Inertia-Location header
    // For regular requests, returns 302 redirect
    return Inertia::location('/dashboard');
}
```

The `Inertia::location()` method automatically detects the request type:
- **Inertia requests**: Returns HTTP 409 with `X-Inertia-Location` header
- **Regular requests**: Returns HTTP 302 with `Location` header

## Version Management

Inertia.js uses version checking to ensure the frontend and backend stay in sync. When the client's version doesn't match the server's version, a full page reload is triggered.

### Automatic Version Detection

By default, the version is automatically detected from your `manifest.json` file:

```php
// Automatically uses dist/manifest.json mtime if it exists
$version = Inertia::version();
```

### Custom Version

You can set a custom version:

```php
// String version
Inertia::version('1.0.0');

// Callback version (evaluated on each request)
Inertia::version(function () {
    return filemtime(Yii::getAlias('@webroot/dist/manifest.json'));
});
```

### Version Mismatch Handling

When a client sends an `X-Inertia-Version` header that doesn't match the current version, the adapter automatically returns a location redirect (409 status) to trigger a full page reload. This ensures users always have the latest assets.

## Configuration

### Root View Path

You can configure the root view path:

```php
Inertia::setRootView('@app/views/custom-inertia.php');
```

### Bootstrap/Initialization

For shared props that should be available on every page, you can set them in your application bootstrap or a common controller:

```php
// In config/bootstrap.php or a base controller
use Crenspire\Yii2Inertia\Inertia;

// Share user data
Inertia::share('user', function () {
    return Yii::$app->user->identity;
});

// Share flash messages
Inertia::share('flash', function () {
    return [
        'success' => Yii::$app->session->getFlash('success'),
        'error' => Yii::$app->session->getFlash('error'),
    ];
});
```

## Troubleshooting

### Version Mismatch Issues

If you're experiencing frequent full page reloads, check:
1. Your version callback is returning a stable value
2. The `manifest.json` file exists and is accessible
3. File permissions allow reading the manifest file

### Redirect Not Working

If redirects aren't working as expected:
1. Ensure you're using `Inertia::location()` instead of Yii's `redirect()`
2. Check that the request has the `X-Inertia` header for Inertia requests
3. Verify the response status code (409 for Inertia, 302 for regular)

### Root View Not Found

If you get "Root view file not found" errors:
1. Verify the path in `Inertia::setRootView()` is correct
2. Check that the view file exists and is readable
3. Ensure Yii aliases are properly configured

## Running the Example

The repository includes a complete example application. To run it:

```bash
# Install dependencies
cd examples/basic
composer install

# Install frontend dependencies
cd vite
npm install

# Build frontend assets
npm run build

# Or run dev server
npm run dev

# Start PHP server
cd ../web
php -S localhost:8000
```

Visit `http://localhost:8000` in your browser.

## Testing

Run the test suite:

```bash
composer install
vendor/bin/phpunit
```

## Requirements

- PHP ^8.1
- Yii2 ~2.0.50

## License

MIT License. See [LICENSE](LICENSE) file for details.

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes.

