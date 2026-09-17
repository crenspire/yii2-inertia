# Merging props

By default a prop returned by a partial reload replaces the current value. Merge props are combined with the existing
value instead — useful for "load more" buttons, chat messages or notifications.

```php
return Inertia::render('Chat/Show', [
    'messages' => Inertia::merge(fn () => $conversation->messagesBefore($cursor)),
]);
```

Merging only applies to partial reloads; a full visit always replaces the value.

## Append, prepend and deep merge

```php
// Append new items (the default)
'messages' => Inertia::merge(fn () => $messages),

// Prepend new items
'notifications' => Inertia::merge(fn () => $notifications)->prepend(),

// Recursively merge objects
'settings' => Inertia::deepMerge(fn () => $settings),
```

## Merging nested lists

When the list is nested in the prop value, name the path to merge:

```php
'feed' => Inertia::merge(fn () => ['data' => $posts, 'pinned' => $pinned])
    ->append('data')
    ->prepend('pinned'),
```

## Matching items

Merging blindly can duplicate items that were already loaded. `matchOn()` updates existing items with the same key
instead of appending them again:

```php
'messages' => Inertia::merge(fn () => $messages)->matchOn('id'),
'feed' => Inertia::merge(fn () => ['data' => $posts])->append('data', matchOn: 'id'),
```

## Resetting

Reset a merge prop to replace its value once, for example when a filter changes:

```js
router.reload({ only: ['messages'], reset: ['messages'] })
```
