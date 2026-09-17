# Testing

## Functional tests

Inertia pages are regular HTTP responses, so functional tests work as usual. Send the `X-Inertia` header to receive the
page object as JSON, or read it from the root view of a full page load.

::: warning Send the asset version
Real Inertia clients always send the `X-Inertia-Version` header. An Inertia `GET` request without it (or with an old
version) receives `409 Conflict` instead of the page, as the protocol requires.
:::

### Codeception

Add helpers to your `FunctionalTester`:

```php
use Crenspire\Yii2Inertia\Inertia;

class FunctionalTester extends \Codeception\Actor
{
    use _generated\FunctionalTesterActions;

    /**
     * Sends the headers the Inertia client sends with every visit.
     */
    public function amUsingInertia(): void
    {
        $this->haveHttpHeader('X-Inertia', 'true');
        $this->haveHttpHeader('X-Inertia-Version', Inertia::getVersion());
        $this->haveHttpHeader('X-Requested-With', 'XMLHttpRequest');
    }

    /**
     * Returns the page object from an Inertia (JSON) or full page (HTML) response.
     */
    public function grabInertiaPage(): array
    {
        $json = json_decode($this->grabPageSource(), true);
        if (is_array($json) && isset($json['component'])) {
            return $json;
        }

        return json_decode($this->grabTextFrom('script[data-page="app"]'), true);
    }

    public function seeInertiaComponent(string $component): void
    {
        $this->assertSame($component, $this->grabInertiaPage()['component']);
    }
}
```

```php
public function adminCanListUsers(FunctionalTester $I): void
{
    $I->amLoggedInAs(User::findOne(['email' => 'admin@example.com']));
    $I->amUsingInertia();
    $I->amOnPage('/users');

    $I->seeResponseCodeIs(200);
    $page = $I->grabInertiaPage();
    $I->assertSame('Users/Index', $page['component']);
    $I->assertArrayNotHasKey('password_hash', $page['props']['users'][0]);
}

public function validationErrorsAreReturned(FunctionalTester $I): void
{
    $I->amUsingInertia();
    $I->sendAjaxPostRequest('/users/create', ['email' => 'not-an-email']);

    $I->assertSame('Email is not a valid email address.', $I->grabInertiaPage()['props']['errors']['email']);
}
```

### Test environments without a frontend build

Rendering the root view needs the Vite manifest. When your tests do not build the frontend, render pages without asset
tags:

```php
// config/test.php
'components' => [
    'inertia' => ['vite' => ['throwOnMissingManifest' => false]],
],
```

## Testing without HTTP

`createPage()` builds the page object for a component and props, using the current request:

```php
$page = Inertia::getManager()->createPage('Users/Index', ['users' => fn () => [['id' => 1]]]);

$this->assertSame([['id' => 1]], $page['props']['users']);
```

## Response format

For Inertia requests `$response->data` holds the encoded JSON string and `$response->format` is `raw`. Decode it to
inspect the page:

```php
$page = json_decode(Yii::$app->response->data, true);
```
