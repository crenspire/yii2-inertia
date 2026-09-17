# Error handling

## Default behaviour

When an Inertia request fails with a non-Inertia response (an HTML error page, a PHP exception), the Inertia client
shows the response in a modal.

Yii treats Inertia visits as AJAX requests, so in debug mode an unexpected exception is shown as a plain-text stack
trace. The [custom error handler](#a-custom-error-handler) below shows the full debug page instead.

## Rendering errors as Inertia pages

In production, render HTTP errors with a page component instead. The simplest way is an error action:

```php
// config/web.php
'components' => [
    'errorHandler' => ['errorAction' => 'site/error'],
],
```

```php
public function actionError(): Response
{
    $exception = Yii::$app->errorHandler->exception;
    $status = $exception instanceof \yii\web\HttpException ? $exception->statusCode : 500;

    return Inertia::render('Error', [
        'status' => $status,
        'message' => $status < 500 ? $exception->getMessage() : 'Something went wrong.',
    ]);
}
```

The error handler sets the status code before running the action, so the page is returned with the correct status.
Yii runs the error action for HTTP exceptions (`yii\base\UserException`) and, when `YII_DEBUG` is off, for all
other exceptions.

## A custom error handler

For more control — for example keeping Yii's detailed exception page for unexpected errors in debug mode while
rendering HTTP errors as pages — extend the error handler:

```php
namespace app\components;

use Crenspire\Yii2Inertia\Inertia;
use Yii;
use yii\web\ErrorHandler;
use yii\web\HttpException;
use yii\web\Response;

class InertiaErrorHandler extends ErrorHandler
{
    protected function renderException($exception)
    {
        if (YII_DEBUG && !$exception instanceof HttpException) {
            parent::renderException($exception);
            return;
        }

        $response = Yii::$app->getResponse();
        $response->isSent = false;
        $response->stream = null;
        $response->data = null;
        $response->content = null;
        $response->setStatusCodeByException($exception);
        $status = $response->getStatusCode();

        try {
            // Inertia visits get a page component; an error while rendering it falls back to Yii's output
            Inertia::render('Error', [
                'status' => $status,
                'message' => $exception instanceof HttpException && $status < 500
                    ? $exception->getMessage()
                    : (Response::$httpStatuses[$status] ?? 'An error occurred'),
            ])->send();
        } catch (\Throwable $e) {
            parent::renderException($exception);
        }
    }

    /**
     * Shows the full debug page in the Inertia error modal instead of a plain-text stack trace.
     */
    protected function shouldRenderSimpleHtml()
    {
        return YII_ENV_TEST || (Yii::$app->request->getIsAjax() && !Inertia::isInertiaRequest());
    }
}
```

```php
'components' => [
    'errorHandler' => ['class' => \app\components\InertiaErrorHandler::class],
],
```

## Server-side rendering errors

A failing server-side render never breaks the page: the error is logged and the page falls back to client-side
rendering. See [server-side rendering](/guide/ssr#failures).

## Encoding errors

Props must be valid UTF-8. Invalid data throws a `JsonException` instead of silently rendering an empty page, so the
problem is visible in your logs.
