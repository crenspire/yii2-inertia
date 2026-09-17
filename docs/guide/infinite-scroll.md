# Infinite scroll

`Inertia::scroll()` prepares a paginated list for the `<InfiniteScroll>` component. It works with any paginated Yii
data provider:

```php
use yii\data\ActiveDataProvider;

public function actionIndex(): Response
{
    return Inertia::render('Posts/Index', [
        'posts' => Inertia::scroll(fn () => new ActiveDataProvider([
            'query' => Post::find()->select(['id', 'title', 'created_at'])->orderBy(['id' => SORT_DESC])->asArray(),
            'pagination' => ['pageSize' => 20],
        ])),
    ]);
}
```

```jsx
import { InfiniteScroll } from '@inertiajs/react'

export default function Index({ posts }) {
  return (
    <InfiniteScroll data="posts">
      {posts.data.map((post) => <PostCard key={post.id} post={post} />)}
    </InfiniteScroll>
  )
}
```

The data provider becomes `{ data: [...] }`, and the adapter adds the pagination state (page parameter, previous, next
and current page) to the page's `scrollProps`. As the user scrolls, the client requests the next page with the
pagination query parameter (`?page=2`) and appends the items; scrolling up in reverse mode prepends them.

## Wrapper key

The list is placed under `data` by default. Choose another key with the second argument:

```php
'posts' => Inertia::scroll(fn () => $provider, 'items'),
```

```jsx
posts.items.map(...)
```

## Custom pagination metadata

For values that are not data providers, provide the metadata yourself — a `ScrollMetadata` instance, or a callback
receiving the value:

```php
use Crenspire\Yii2Inertia\Props\ScrollMetadata;

'posts' => Inertia::scroll(
    fn () => ['data' => $posts],
    'data',
    fn () => new ScrollMetadata('cursor', previousPage: null, nextPage: $nextCursor, currentPage: $cursor),
),
```

`ScrollMetadata::fromPagination()` and `ScrollMetadata::fromDataProvider()` build it from Yii objects. Custom
implementations can implement `ProvidesScrollMetadata`.

## Deferring the first page

```php
'posts' => Inertia::scroll(fn () => $provider)->defer(),
```
