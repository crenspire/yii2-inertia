# Prop types

Prop types change when and how a prop is sent. They are created through the `Inertia` facade and live in the
`Crenspire\Yii2Inertia\Props` namespace. All of them can be nested at any depth in the props.

| Factory | Class | Initial visit | Partial reload | Guide |
|---|---|---|---|---|
| `Inertia::optional()` | `OptionalProp` | not sent | when requested | [Partial reloads](/guide/partial-reloads#optional-props) |
| `Inertia::defer()` | `DeferProp` | announced, loaded right after | when requested | [Deferred props](/guide/deferred-props) |
| `Inertia::always()` | `AlwaysProp` | sent | always sent | [Partial reloads](/guide/partial-reloads#always-props) |
| `Inertia::merge()` | `MergeProp` | sent | sent and merged | [Merging props](/guide/merging-props) |
| `Inertia::deepMerge()` | `MergeProp` | sent | sent and deep merged | [Merging props](/guide/merging-props) |
| `Inertia::once()` | `OnceProp` | sent unless remembered | when requested | [Once props](/guide/once-props) |
| `Inertia::scroll()` | `ScrollProp` | sent with pagination metadata | sent and merged | [Infinite scroll](/guide/infinite-scroll) |

## Modifiers

### Merging

Available on `MergeProp`, `DeferProp` and `ScrollProp`.

| Method | Description |
|---|---|
| `merge()` | Merge the value instead of replacing it |
| `deepMerge()` | Merge recursively |
| `append(bool\|string\|array $path = true, ?string $matchOn = null)` | Append at the root or at nested paths |
| `prepend(bool\|string\|array $path = true, ?string $matchOn = null)` | Prepend at the root or at nested paths |
| `matchOn(string\|array $keys)` | Keys identifying items to update in place |

### Once

Available on `OnceProp`, `OptionalProp`, `DeferProp` and `MergeProp`.

| Method | Description |
|---|---|
| `once(bool $value = true, ?string $as = null, DateTimeInterface\|DateInterval\|int\|null $until = null)` | Resolve once and remember on the client |
| `as(BackedEnum\|UnitEnum\|string $key)` | Remember under a custom key |
| `until(DateTimeInterface\|DateInterval\|int $delay)` | Expire after a date, interval or number of seconds |
| `fresh(bool $value = true)` | Send the value even if the client has it |

### Deferring

Available on `DeferProp` and `ScrollProp`.

| Method | Description |
|---|---|
| `defer(?string $group = null)` | Load in a separate request, in the given group |

`DeferProp` also accepts `rescue` in its constructor (`Inertia::defer($callback, $group, rescue: true)`).

## Scroll metadata

`ScrollMetadata` describes the pagination state of a scroll prop:

```php
new ScrollMetadata(string $pageName, int|string|null $previousPage = null, int|string|null $nextPage = null, int|string|null $currentPage = null)

ScrollMetadata::fromPagination(\yii\data\Pagination $pagination): ScrollMetadata
ScrollMetadata::fromDataProvider(\yii\data\DataProviderInterface $dataProvider): ScrollMetadata
```

Implement `ProvidesScrollMetadata` for custom pagination objects.

## Property providers

| Interface | Method | Description |
|---|---|---|
| `ProvidesInertiaProperties` | `toInertiaProperties(RenderContext $context): iterable` | Adds several props; used without a key |
| `ProvidesInertiaProperty` | `toInertiaProperty(PropertyContext $prop): mixed` | Computes a single prop value |

`RenderContext` has the `component` name and the `request`. `PropertyContext` has the prop `key` (dot-notation path),
its sibling `props` and the `request`.

## Value resolution

Every prop value is resolved in this order before it is encoded:

1. Invokable objects and closures are called.
2. `ProvidesInertiaProperty` objects are asked for their value.
3. `yii\base\Arrayable` objects are converted with `toArray()`.
4. `JsonSerializable` objects are serialized.
5. Arrays are resolved recursively.
