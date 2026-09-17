<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Tests;

use Crenspire\Yii2Inertia\Inertia;
use Crenspire\Yii2Inertia\Manager;
use Crenspire\Yii2Inertia\Tests\Support\ArraySession;
use Crenspire\Yii2Inertia\Tests\Support\TestController;
use Crenspire\Yii2Inertia\Tests\Support\TestManager;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\Application;
use yii\web\Response;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    private array $originalServer = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalServer = $_SERVER;
        $_SESSION = [];
        $_COOKIE = [];
        TestController::$ran = false;
    }

    protected function tearDown(): void
    {
        Yii::$app = null;
        $_SERVER = $this->originalServer;
        $_SESSION = [];
        $_COOKIE = [];
        $_GET = [];
        $_POST = [];
        parent::tearDown();
    }

    /**
     * Creates a web application for a simulated request. Calling it again simulates the next
     * request of the same browser (the session is kept).
     *
     * @param array<string, string> $headers
     * @param array<string, mixed> $config
     */
    protected function createApp(string $url = '/', string $method = 'GET', array $headers = [], array $config = []): Application
    {
        foreach (array_keys($_SERVER) as $key) {
            if (str_starts_with($key, 'HTTP_')) {
                unset($_SERVER[$key]);
            }
        }
        $query = (string) parse_url($url, PHP_URL_QUERY);
        $_SERVER = array_merge($_SERVER, [
            'REQUEST_METHOD' => $method,
            'REQUEST_URI' => $url,
            'QUERY_STRING' => $query,
            'HTTP_HOST' => 'localhost',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => '80',
            'SCRIPT_NAME' => '/index.php',
            'SCRIPT_FILENAME' => __DIR__ . '/fixtures/web/index.php',
            'PHP_SELF' => '/index.php',
        ]);
        foreach ($headers as $name => $value) {
            $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $name))] = $value;
        }
        $_GET = [];
        parse_str($query, $_GET);

        return new Application(ArrayHelper::merge([
            'id' => 'inertia-test',
            'basePath' => __DIR__,
            'vendorPath' => dirname(__DIR__) . '/vendor',
            'aliases' => ['@fixtures' => __DIR__ . '/fixtures'],
            'controllerMap' => ['test' => TestController::class],
            'components' => [
                'request' => ['cookieValidationKey' => 'test-key'],
                'session' => ['class' => ArraySession::class],
                'urlManager' => ['enablePrettyUrl' => true, 'showScriptName' => false],
                'inertia' => [
                    'class' => TestManager::class,
                    'rootView' => '@fixtures/views/app.php',
                ],
            ],
        ], $config));
    }

    /**
     * @param array<string, string> $extra
     * @return array<string, string>
     */
    protected function inertiaHeaders(array $extra = []): array
    {
        return array_merge(['X-Inertia' => 'true', 'X-Requested-With' => 'XMLHttpRequest'], $extra);
    }

    protected function manager(): Manager
    {
        return Inertia::getManager();
    }

    /**
     * @return array<string, mixed>
     */
    protected function page(Response $response): array
    {
        return json_decode((string) $response->data, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Extracts the page object from the HTML of an initial page load.
     *
     * @return array<string, mixed>
     */
    protected function htmlPage(Response $response, string $id = 'app'): array
    {
        $pattern = '#<script type="application/json" data-page="' . preg_quote($id, '#') . '">(.*?)</script><div id="' . preg_quote($id, '#') . '"></div>#s';
        $this->assertMatchesRegularExpression($pattern, (string) $response->data);
        preg_match($pattern, (string) $response->data, $matches);

        return json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
    }

    protected function send(Response $response): Response
    {
        $response->trigger(Response::EVENT_BEFORE_SEND);

        return $response;
    }
}
