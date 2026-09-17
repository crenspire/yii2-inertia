# Once props

Once props are resolved once and remembered by the client. When the user visits another page using the same prop, the
server skips it and the client reuses the value it already has. Use them for reference data that rarely changes:
countries, plans, permissions.

```php
return Inertia::render('Billing/Plans', [
    'plans' => Inertia::once(fn () => Plan::find()->asArray()->all()),
]);
```

The client sends the keys of the once props it holds in the `X-Inertia-Except-Once-Props` header and the adapter does
not evaluate them again.

## Sharing once props

```php
Inertia::shareOnce('countries', fn () => Country::find()->select(['code', 'name'])->asArray()->all());
```

## Expiration

```php
'plans' => Inertia::once(fn () => Plan::all())->until(3600),              // seconds
'plans' => Inertia::once(fn () => Plan::all())->until(new DateInterval('P1D')),
'plans' => Inertia::once(fn () => Plan::all())->until(new DateTimeImmutable('tomorrow')),
```

## Custom keys

By default a once prop is remembered under its prop path. Give props on different pages the same key to share the value:

```php
// Billing/Plans
'plans' => Inertia::once(fn () => Plan::all())->as('plans'),
// Billing/Upgrade
'availablePlans' => Inertia::once(fn () => Plan::all())->as('plans'),
```

## Forcing a refresh

Send the value even if the client already has it, for example after it changed:

```php
'plans' => Inertia::once(fn () => Plan::all())->fresh($plansWereUpdated),
```

## Combining with other prop types

Optional, deferred and merge props can be loaded once too:

```php
'countries' => Inertia::defer(fn () => Country::all())->once(),
'report' => Inertia::optional(fn () => Report::build())->once(),
```
