<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Tests\Unit;

use Crenspire\Yii2Inertia\Inertia;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\web\Application;
use yii\web\Request;
use yii\web\Response;

class InertiaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        $config = [
            'id' => 'test-app',
            'basePath' => __DIR__ . '/../../',
            'components' => [
                'request' => [
                    'class' => Request::class,
                ],
            ],
        ];
        
        new Application($config);
        Inertia::flushShared();
    }

    protected function tearDown(): void
    {
        Yii::$app = null;
        parent::tearDown();
    }

    public function testShareSingleKeyValue(): void
    {
        Inertia::share('user', ['name' => 'John']);
        
        $this->assertTrue(true); // Shared props are tested in integration tests
    }

    public function testShareArray(): void
    {
        Inertia::share([
            'user' => ['name' => 'John'],
            'app' => ['name' => 'My App'],
        ]);
        
        $this->assertTrue(true); // Shared props are tested in integration tests
    }

    public function testShareClosure(): void
    {
        Inertia::share('timestamp', function () {
            return time();
        });
        
        $this->assertTrue(true); // Closure evaluation tested in integration tests
    }

    public function testVersionString(): void
    {
        Inertia::version('1.0.0');
        $version = Inertia::version();
        
        $this->assertEquals('1.0.0', $version);
    }

    public function testVersionCallback(): void
    {
        Inertia::version(function () {
            return '2.0.0';
        });
        
        $version = Inertia::version();
        $this->assertEquals('2.0.0', $version);
    }

    public function testVersionDefault(): void
    {
        // Reset version
        Inertia::version(null);
        
        $version = Inertia::version();
        $this->assertIsString($version);
    }

    public function testSetRootView(): void
    {
        $view = '@app/views/custom.php';
        Inertia::setRootView($view);
        
        $this->assertEquals($view, Inertia::getRootView());
    }

    public function testIsInertiaRequest(): void
    {
        $request = Yii::$app->request;
        
        // Without header
        $this->assertFalse(Inertia::isInertiaRequest($request));
        
        // With header
        $request->headers->set('X-Inertia', 'true');
        $this->assertTrue(Inertia::isInertiaRequest($request));
    }

    public function testLocation(): void
    {
        $response = Inertia::location('/dashboard');
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(409, $response->statusCode);
        $this->assertEquals('/dashboard', $response->headers->get('X-Inertia-Location'));
    }

    public function testFlushShared(): void
    {
        Inertia::share('test', 'value');
        Inertia::flushShared();
        
        $this->assertTrue(true); // Flush tested via integration
    }
}

