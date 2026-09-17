<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Tests\Integration;

use Crenspire\Yii2Inertia\Bootstrap;
use Crenspire\Yii2Inertia\Inertia;
use Crenspire\Yii2Inertia\Tests\Support\TestController;
use Crenspire\Yii2Inertia\Tests\Support\TestManager;
use Crenspire\Yii2Inertia\Tests\TestCase;
use Yii;
use yii\web\Application;
use yii\web\Response;

class LifecycleTest extends TestCase
{
    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $config
     */
    private function bootApp(string $url = '/', string $method = 'GET', array $headers = [], array $config = []): Application
    {
        $app = $this->createApp($url, $method, $headers, array_merge(['bootstrap' => [Bootstrap::class]], $config));
        $app->trigger(Application::EVENT_BEFORE_REQUEST);

        return $app;
    }

    private function runRoute(string $route): Response
    {
        $result = Yii::$app->runAction($route);

        return $this->send($result instanceof Response ? $result : Yii::$app->getResponse());
    }

    public function testBootstrapIsIdempotent(): void
    {
        $this->bootApp('/', 'GET', [], ['bootstrap' => ['inertia', Bootstrap::class]]);

        $this->send(Yii::$app->getResponse());

        /** @var TestManager $manager */
        $manager = $this->manager();
        $this->assertSame(1, $manager->beforeSendCalls);
    }

    public function testOutdatedAssetVersionForcesFullReload(): void
    {
        $this->bootApp('/test/index?x=1', 'GET', $this->inertiaHeaders(['X-Inertia-Version' => 'old']));
        Inertia::version('new');

        $response = $this->runRoute('test/index');

        $this->assertFalse(TestController::$ran);
        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame('http://localhost/test/index?x=1', $response->headers->get('X-Inertia-Location'));
        $this->assertSame('new', $response->headers->get('X-Inertia-Version'));
    }

    public function testCurrentAssetVersionRunsAction(): void
    {
        $this->bootApp('/test/index', 'GET', $this->inertiaHeaders(['X-Inertia-Version' => 'v2']));
        Inertia::version('v2');

        $response = $this->runRoute('test/index');

        $this->assertTrue(TestController::$ran);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Test/Index', $this->page($response)['component']);
    }

    public function testVersionIsOnlyCheckedForGetRequests(): void
    {
        $this->bootApp('/test/redirect', 'POST', $this->inertiaHeaders(['X-Inertia-Version' => 'old']));
        Inertia::version('new');

        $this->runRoute('test/redirect');

        $this->assertTrue(TestController::$ran);
    }

    public function testRedirectsWorkForInertiaRequests(): void
    {
        $this->bootApp('/test/redirect', 'POST', $this->inertiaHeaders());

        $response = $this->runRoute('test/redirect');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('http://localhost/test/index', $response->headers->get('Location'));
        $this->assertFalse($response->headers->has('X-Redirect'));
    }

    public function testRedirectAfterPutPatchDeleteUses303(): void
    {
        foreach (['PUT', 'PATCH', 'DELETE'] as $method) {
            $this->bootApp('/test/redirect', $method, $this->inertiaHeaders());
            $this->assertSame(303, $this->runRoute('test/redirect')->getStatusCode(), $method);
        }

        $this->bootApp('/test/redirect', 'PUT');
        $this->assertSame(302, $this->runRoute('test/redirect')->getStatusCode(), 'non-Inertia requests are untouched');
    }

    public function testEmptyResponseRedirectsBack(): void
    {
        $this->bootApp('/test/empty', 'PUT', $this->inertiaHeaders(['Referer' => 'http://localhost/users/1/edit']));

        $response = $this->runRoute('test/empty');

        $this->assertTrue(TestController::$ran);
        $this->assertSame(303, $response->getStatusCode());
        $this->assertSame('http://localhost/users/1/edit', $response->headers->get('Location'));

        $this->bootApp('/test/empty', 'POST');
        $this->assertSame(200, $this->runRoute('test/empty')->getStatusCode(), 'non-Inertia requests are untouched');
    }

    public function testRedirectWithFragment(): void
    {
        $this->bootApp('/test/fragment', 'POST', $this->inertiaHeaders());
        $response = $this->runRoute('test/fragment');
        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame('http://localhost/test/index#comments', $response->headers->get('X-Inertia-Redirect'));
        $this->assertFalse($response->headers->has('Location'));

        $this->bootApp('/test/fragment', 'GET', $this->inertiaHeaders(['Purpose' => 'prefetch', 'X-Inertia-Version' => Inertia::getVersion()]));
        $this->assertSame(302, $this->runRoute('test/fragment')->getStatusCode());

        $this->bootApp('/test/fragment', 'POST');
        $this->assertSame(302, $this->runRoute('test/fragment')->getStatusCode());
    }

    public function testVaryHeaderIsAddedToEveryResponse(): void
    {
        $this->bootApp();
        $response = Yii::$app->getResponse();
        $response->headers->set('Vary', 'Accept-Encoding');

        $this->send($response);

        $this->assertSame('Accept-Encoding, X-Inertia', $response->headers->get('Vary'));
    }

    public function testCsrfTokenCookieRoundTrip(): void
    {
        $this->bootApp('/test/index');
        $this->runRoute('test/index');

        /** @var TestManager $manager */
        $manager = $this->manager();
        $token = $manager->sentCookies['XSRF-TOKEN'] ?? null;
        $this->assertNotNull($token);
        $csrfCookie = Yii::$app->getResponse()->getCookies()->getValue('_csrf');
        $this->assertNotNull($csrfCookie);

        $_COOKIE['_csrf'] = Yii::$app->getSecurity()->hashData(serialize(['_csrf', $csrfCookie]), 'test-key');
        $this->bootApp('/users', 'POST', $this->inertiaHeaders(['X-XSRF-TOKEN' => $token]));
        $this->assertTrue(Yii::$app->getRequest()->validateCsrfToken());

        $this->bootApp('/users', 'POST', $this->inertiaHeaders());
        $this->assertFalse(Yii::$app->getRequest()->validateCsrfToken());
    }

    public function testCsrfCookieIsOnlySentForInertiaResponses(): void
    {
        $this->bootApp();
        $this->send(Yii::$app->getResponse());

        /** @var TestManager $manager */
        $manager = $this->manager();
        $this->assertSame([], $manager->sentCookies);
    }

    public function testCsrfCookieCanBeDisabled(): void
    {
        $this->bootApp('/test/index', 'GET', ['X-XSRF-TOKEN' => 'token'], ['components' => ['inertia' => ['enableCsrfCookie' => false]]]);
        $this->runRoute('test/index');

        /** @var TestManager $manager */
        $manager = $this->manager();
        $this->assertSame([], $manager->sentCookies);
        $this->assertFalse(Yii::$app->getRequest()->headers->has('X-CSRF-Token'));
    }

    public function testJsonRequestBodiesAreParsed(): void
    {
        $this->bootApp('/users', 'POST', $this->inertiaHeaders(), ['components' => ['request' => ['parsers' => []]]]);
        Yii::$app->getRequest()->setRawBody('{"name":"Ann"}');
        Yii::$app->getRequest()->headers->set('Content-Type', 'application/json');
        $_SERVER['CONTENT_TYPE'] = 'application/json';

        $this->assertSame(['name' => 'Ann'], Yii::$app->getRequest()->post());
    }

    public function testCustomJsonParserIsKept(): void
    {
        $this->bootApp('/', 'GET', [], ['components' => ['request' => ['parsers' => ['application/json' => 'app\\MyParser']]]]);

        $this->assertSame('app\\MyParser', Yii::$app->getRequest()->parsers['application/json']);
    }

    public function testLocation(): void
    {
        $this->createApp('/', 'GET', $this->inertiaHeaders());
        $response = Inertia::location('https://example.com/pay');
        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame('https://example.com/pay', $response->headers->get('X-Inertia-Location'));
        $this->assertFalse($response->headers->has('Location'));

        $this->createApp();
        $response = Inertia::location(['/test/index', 'id' => 5]);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('http://localhost/test/index?id=5', $response->headers->get('Location'));
        $this->assertFalse($response->headers->has('X-Inertia-Location'));
    }

    public function testBack(): void
    {
        $this->createApp('/users/1', 'PUT', $this->inertiaHeaders(['Referer' => 'http://localhost/users/1/edit']));
        $response = Inertia::back();
        $this->assertSame(303, $response->getStatusCode());
        $this->assertSame('http://localhost/users/1/edit', $response->headers->get('Location'));

        $this->createApp('/users', 'POST', $this->inertiaHeaders());
        $response = Inertia::back(['/test/index']);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('http://localhost/test/index', $response->headers->get('Location'));
    }
}
