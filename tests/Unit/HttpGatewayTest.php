<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Tests\Unit;

use Crenspire\Yii2Inertia\Ssr\HttpGateway;
use Crenspire\Yii2Inertia\Ssr\SsrException;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\log\Logger;

class HttpGatewayTest extends TestCase
{
    /** @var resource|null */
    private static $server = null;

    private static string $url = '';

    public static function setUpBeforeClass(): void
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $port = (int) substr((string) stream_socket_get_name($socket, false), strrpos((string) stream_socket_get_name($socket, false), ':') + 1);
        fclose($socket);

        self::$url = "http://127.0.0.1:{$port}";
        self::$server = proc_open(
            [PHP_BINARY, '-S', "127.0.0.1:{$port}", __DIR__ . '/../fixtures/ssr/router.php'],
            [['pipe', 'r'], ['file', '/dev/null', 'w'], ['file', '/dev/null', 'w']],
            $pipes,
        );

        $deadline = microtime(true) + 5;
        while (microtime(true) < $deadline) {
            $connection = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.1);
            if ($connection !== false) {
                fclose($connection);

                return;
            }
            usleep(50_000);
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Yii::setLogger(new Logger());
    }

    public function testRendersThroughProductionServer(): void
    {
        $response = (new HttpGateway(['url' => self::$url . '/']))->dispatch(['component' => 'Home', 'props' => ['name' => 'Ann']]);

        $this->assertNotNull($response);
        $this->assertSame('<title>Home</title>', $response->head);
        $this->assertSame('<div id="app" data-server-rendered="true">/render:Ann</div>', $response->body);
    }

    public function testRendersThroughViteDevServer(): void
    {
        $gateway = new HttpGateway(['url' => 'http://127.0.0.1:9', 'devServerUrl' => self::$url, 'bundle' => '/missing/ssr.js']);

        $this->assertStringContainsString('/__inertia_ssr:Ann', $gateway->dispatch(['component' => 'Home', 'props' => ['name' => 'Ann']])->body);
    }

    public function testMissingBundleSkipsRendering(): void
    {
        $gateway = new HttpGateway(['url' => self::$url, 'bundle' => '/missing/ssr.js']);

        $this->assertNull($gateway->dispatch(['component' => 'Home', 'props' => ['name' => 'Ann']]));
    }

    public function testRenderErrorsFallBackToClientRendering(): void
    {
        $logger = new Logger();
        Yii::setLogger($logger);

        $this->assertNull((new HttpGateway(['url' => self::$url]))->dispatch(['component' => 'Broken', 'props' => []]));
        $this->assertStringContainsString('window is not defined', $logger->messages[0][0]);
        $this->assertStringContainsString('src/pages/Broken.jsx:3:1', $logger->messages[0][0]);
    }

    public function testRenderErrorsCanThrow(): void
    {
        try {
            (new HttpGateway(['url' => self::$url, 'throwOnError' => true]))->dispatch(['component' => 'Broken', 'props' => []]);
            $this->fail('Expected an SsrException');
        } catch (SsrException $e) {
            $this->assertStringContainsString('Hint: Use onMounted', $e->getMessage());
            $this->assertSame('browser-api', $e->details['type']);
        }
    }

    public function testUnreachableServer(): void
    {
        $gateway = new HttpGateway(['url' => 'http://127.0.0.1:9', 'timeout' => 0.5]);

        $this->assertNull($gateway->dispatch(['component' => 'Home']));
        $this->assertFalse($gateway->isHealthy());
    }

    public function testHealthCheck(): void
    {
        $this->assertTrue((new HttpGateway(['url' => self::$url]))->isHealthy());
    }
}
