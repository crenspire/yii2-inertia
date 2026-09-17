# Flash data

Flash data is one-time data for the next rendered page, typically a success message or a toast after a redirect.

```php
use Crenspire\Yii2Inertia\Inertia;

Inertia::flash('success', 'Profile updated.');
Inertia::flash(['toast' => ['type' => 'success', 'message' => 'Saved']]);

return $this->redirect(['profile/index']);
```

The next rendered page receives it in the `flash` property of the page object (not as a prop), and it is removed from
the session. Flash data is not stored in the browser history, so it does not reappear when navigating back.

::: code-group

```jsx [React]
import { usePage } from '@inertiajs/react'

export default function FlashMessage() {
  const { flash } = usePage()

  return flash.success ? <div className="alert">{flash.success}</div> : null
}
```

```vue [Vue]
<script setup>
import { usePage } from '@inertiajs/vue3'

const page = usePage()
</script>

<template>
  <div v-if="page.flash.success" class="alert">{{ page.flash.success }}</div>
</template>
```

:::

To react to flash data once, for example to show a toast, listen to the `flash` event:

```js
import { router } from '@inertiajs/react'

router.on('flash', (event) => {
  toast(event.detail.flash.success)
})
```

`Inertia::getManager()->getFlashed()` returns the data flashed so far in the current request.

## Using Yii session flashes

Yii's own session flashes still work. Share them as a prop if you prefer:

```php
Inertia::share('flash', fn () => (object) Yii::$app->session->getAllFlashes(true));
```

Note that props, unlike Inertia flash data, are stored in the browser history.
