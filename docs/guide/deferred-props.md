# Deferred props

Deferred props are left out of the initial response. The page renders immediately and the client fetches the deferred
props in a separate request right after, which keeps slow data from delaying the whole page.

```php
return Inertia::render('Dashboard', [
    'user' => $user->toArray(['id', 'name']),
    'activity' => Inertia::defer(fn () => Activity::latestFor($user)),
    'stats' => Inertia::defer(fn () => Stats::summary()),
]);
```

```jsx
import { Deferred } from '@inertiajs/react'

<Deferred data="activity" fallback={<div>Loading…</div>}>
  <ActivityFeed activity={activity} />
</Deferred>
```

## Groups

Deferred props in the same group are fetched in one request; different groups are fetched in parallel. Props without a
group belong to the `default` group:

```php
'activity' => Inertia::defer(fn () => Activity::latest()),
'stats' => Inertia::defer(fn () => Stats::summary(), 'sidebar'),
'teams' => Inertia::defer(fn () => Team::forUser($user), 'sidebar'),
```

## Rescuing failures

By default an exception in a deferred prop fails its request. Pass `rescue: true` to report the exception to the log
and omit the prop instead; its name is listed in the page's `rescuedProps`, and the `<Deferred>` component renders its
`rescue` slot:

```php
'recommendations' => Inertia::defer(fn () => $this->recommendationService->for($user), rescue: true),
```

```jsx
<Deferred data="recommendations" fallback={<Spinner />} rescue={<p>Recommendations are unavailable.</p>}>
  <Recommendations items={recommendations} />
</Deferred>
```

## Combining with other behaviours

Deferred props can be [merged](/guide/merging-props) and [loaded once](/guide/once-props):

```php
'comments' => Inertia::defer(fn () => $post->getComments()->asArray()->all())->merge(),
'countries' => Inertia::defer(fn () => Country::all())->once(),
```

Deferred props can be nested at any depth, for example inside a shared prop:
`'auth' => ['user' => $user, 'permissions' => Inertia::defer(fn () => ...)]` is announced as `auth.permissions`.
