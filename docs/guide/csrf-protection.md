# CSRF protection

Yii's CSRF validation works with Inertia without any code in your application.

## How it works

- With every Inertia response (and every rendered page), the adapter sets a JavaScript-readable `XSRF-TOKEN` cookie
  containing a masked CSRF token.
- The Inertia HTTP client reads the cookie and sends its value in the `X-XSRF-TOKEN` header of every request.
- Before the request is handled, the adapter copies the header to the header Yii validates
  ([`Request::$csrfHeader`](https://www.yiiframework.com/doc/api/2.0/yii-web-request#$csrfHeader-detail),
  `X-CSRF-Token` by default).

Your controllers keep `enableCsrfValidation` and `Request::validateCsrfToken()` behaves as usual. A request without a
valid token fails with `400 Bad Request`.

The cookie follows the path, domain, `secure` and `sameSite` settings of Yii's
[`csrfCookie`](https://www.yiiframework.com/doc/api/2.0/yii-web-request#$csrfCookie-detail). It is a native cookie
because Yii signs its response cookies when cookie validation is enabled, which would make the token unreadable.

## Handling expired tokens

An expired session makes the next form submission fail with `400 Bad Request`, which Inertia shows in an error modal.
To send the user back with a message instead, check the token in a base controller:

```php
public function beforeAction($action)
{
    if (
        $this->enableCsrfValidation
        && Inertia::isInertiaRequest($this->request)
        && !$this->request->validateCsrfToken()
    ) {
        Inertia::flash('error', 'Your session has expired. Please try again.');
        Inertia::back();

        return false;
    }

    return parent::beforeAction($action);
}
```

## Custom cookie and header names

Cookies are shared by all ports of a host. If another application on the same host also uses an `XSRF-TOKEN` cookie
(Laravel, Django, Angular apps in local development), the tokens overwrite each other. Rename the cookie and header on
both sides:

```php
'inertia' => [
    'csrfCookieName' => 'YII-XSRF-TOKEN',
    'csrfHeaderName' => 'X-YII-XSRF-TOKEN',
],
```

```js
createInertiaApp({
  http: { xsrfCookieName: 'YII-XSRF-TOKEN', xsrfHeaderName: 'X-YII-XSRF-TOKEN' },
  // ...
})
```

## Disabling the cookie

Set [`enableCsrfCookie`](/reference/configuration#enablecsrfcookie) to `false` if you provide the token another way,
for example a `<meta>` tag read by your own client code.
