# Redirects

## Standard redirects

Use Yii's regular redirects after form submissions. They work for Inertia visits without any change:

```php
public function actionStore(): Response
{
    // ... save the model

    return $this->redirect(['user/index']);
}
```

Behind the scenes the adapter adjusts redirects for Inertia (XHR) requests:

- **Yii's AJAX redirect.** For AJAX requests `Response::redirect()` replaces the `Location` header with `X-Redirect`,
  which an XHR cannot follow. The adapter restores the `Location` header.
- **`303 See Other`.** A `302` redirect after a `PUT`, `PATCH` or `DELETE` request becomes `303`, so the browser follows
  it with a `GET` instead of repeating the original method.

## Redirecting back

`Inertia::back()` redirects to the previous page (the `Referer` header), falling back to the home page or a given URL.
It uses `303` after `PUT`, `PATCH` and `DELETE`:

```php
return Inertia::back();
return Inertia::back(['user/index']); // fallback when there is no referrer
```

An action that returns nothing for an Inertia request also redirects back.

## External redirects

A redirect to a page outside the Inertia application — another site, a payment provider, a non-Inertia page of your
application — must be a full page visit. Use `Inertia::location()`:

```php
return Inertia::location('https://checkout.example.com/session/42');
return Inertia::location(['site/legacy-report']);
```

For Inertia requests it responds with `409 Conflict` and an `X-Inertia-Location` header, and the client navigates with
`window.location`. For regular requests it is a normal `302` redirect.

## Redirects with a fragment

An XHR cannot see the `#fragment` of a redirect target. When a redirect for an Inertia request points at a URL with a
fragment, the adapter responds with `409 Conflict` and an `X-Inertia-Redirect` header, and the client visits the URL
itself, keeping the fragment:

```php
return $this->redirect(['post/view', 'id' => $post->id, '#' => 'comment-' . $comment->id]);
```

Prefetch requests are not converted.

To keep the fragment of the *original* visit across an ordinary redirect (e.g. `/docs#installation` redirecting to
`/docs/v2`), call `Inertia::preserveFragment()` before redirecting.
