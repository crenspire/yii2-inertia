# History encryption

Inertia stores page data in the browser history so the back button works without a request. After logging out, that
data could still be displayed by navigating back. History encryption encrypts the page data in the history with a key
kept in the session storage, and clearing the history makes old entries unreadable.

## Encrypting the history

For all pages:

```php
'inertia' => [
    'encryptHistory' => true,
],
```

For a single response, or from a filter protecting authenticated areas:

```php
Inertia::encryptHistory();
```

History encryption uses the browser's `crypto.subtle` API, which requires a secure context (HTTPS or `localhost`).

## Clearing the history

Call `Inertia::clearHistory()` when the user logs out. The next rendered page tells the client to rotate the encryption
key, which makes all previous history entries unreadable:

```php
public function actionLogout(): Response
{
    Yii::$app->user->logout();
    Inertia::clearHistory();

    return $this->goHome();
}
```
