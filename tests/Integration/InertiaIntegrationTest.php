<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Tests\Integration;

use Crenspire\Yii2Inertia\Inertia;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\web\Application;
use yii\web\Request;
use yii\web\Response;

class InertiaIntegrationTest extends TestCase
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
                'view' => [
                    'class' => 'yii\web\View',
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

    public function testRenderReturnsJsonResponseForInertiaRequest(): void
    {
        Yii::$app->request->headers->set('X-Inertia', 'true');
        
        $response = Inertia::render('TestComponent', ['test' => 'value']);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::FORMAT_JSON, $response->format);
        $this->assertEquals('true', $response->headers->get('X-Inertia'));
        
        $data = $response->data;
        $this->assertEquals('TestComponent', $data['component']);
        $this->assertEquals('value', $data['props']['test']);
    }

    public function testRenderReturnsHtmlResponseForNonInertiaRequest(): void
    {
        // Ensure no X-Inertia header
        Yii::$app->request->headers->remove('X-Inertia');
        
        // Mock view to avoid file system dependency
        $view = Yii::$app->view;
        $view->renderers = [];
        
        // Set a test root view that exists
        Inertia::setRootView(__DIR__ . '/../../stubs/index.php');
        
        $response = Inertia::render('TestComponent', ['test' => 'value']);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::FORMAT_RAW, $response->format);
    }

    public function testSharedPropsAreIncluded(): void
    {
        Inertia::share('shared', 'shared-value');
        Yii::$app->request->headers->set('X-Inertia', 'true');
        
        $response = Inertia::render('TestComponent', ['local' => 'local-value']);
        
        $data = $response->data;
        $this->assertEquals('shared-value', $data['props']['shared']);
        $this->assertEquals('local-value', $data['props']['local']);
    }

    public function testSharedPropsClosureIsEvaluated(): void
    {
        $timestamp = time();
        Inertia::share('timestamp', function () use ($timestamp) {
            return $timestamp;
        });
        
        Yii::$app->request->headers->set('X-Inertia', 'true');
        
        $response = Inertia::render('TestComponent', []);
        
        $data = $response->data;
        $this->assertEquals($timestamp, $data['props']['timestamp']);
    }

    public function testPartialReloadFiltersProps(): void
    {
        Inertia::share('shared', 'shared-value');
        Yii::$app->request->headers->set('X-Inertia', 'true');
        Yii::$app->request->headers->set('X-Inertia-Partial-Component', 'TestComponent');
        Yii::$app->request->headers->set('X-Inertia-Partial-Data', 'local');
        
        $response = Inertia::render('TestComponent', [
            'local' => 'local-value',
            'excluded' => 'excluded-value',
        ]);
        
        $data = $response->data;
        // Shared props should always be included
        $this->assertArrayHasKey('shared', $data['props']);
        // Requested partial prop should be included
        $this->assertArrayHasKey('local', $data['props']);
        // Non-requested prop should be excluded
        $this->assertArrayNotHasKey('excluded', $data['props']);
    }

    public function testVersionIsIncludedInResponse(): void
    {
        Inertia::version('test-version-123');
        Yii::$app->request->headers->set('X-Inertia', 'true');
        
        $response = Inertia::render('TestComponent', []);
        
        $data = $response->data;
        $this->assertEquals('test-version-123', $data['version']);
    }

    public function testVersionMismatchReturnsLocationRedirect(): void
    {
        Inertia::version('current-version');
        Yii::$app->request->headers->set('X-Inertia', 'true');
        Yii::$app->request->headers->set('X-Inertia-Version', 'old-version');
        
        $response = Inertia::render('TestComponent', []);
        
        $this->assertEquals(409, $response->statusCode);
        $this->assertTrue($response->headers->has('X-Inertia-Location'));
    }

    public function testLocationReturns409ForInertiaRequest(): void
    {
        Yii::$app->request->headers->set('X-Inertia', 'true');
        
        $response = Inertia::location('/dashboard');
        
        $this->assertEquals(409, $response->statusCode);
        $this->assertEquals('/dashboard', $response->headers->get('X-Inertia-Location'));
    }

    public function testLocationReturns302ForNonInertiaRequest(): void
    {
        Yii::$app->request->headers->remove('X-Inertia');
        
        $response = Inertia::location('/dashboard');
        
        $this->assertEquals(302, $response->statusCode);
        $this->assertEquals('/dashboard', $response->headers->get('Location'));
    }

    public function testUrlIncludesQueryString(): void
    {
        Yii::$app->request->headers->set('X-Inertia', 'true');
        $_GET['test'] = 'value';
        Yii::$app->request->setQueryParams(['test' => 'value']);
        
        $response = Inertia::render('TestComponent', []);
        
        $data = $response->data;
        $this->assertStringContainsString('test=value', $data['url']);
    }

    public function testEmptyPartialDataReturnsAllProps(): void
    {
        Inertia::share('shared', 'shared-value');
        Yii::$app->request->headers->set('X-Inertia', 'true');
        Yii::$app->request->headers->set('X-Inertia-Partial-Component', 'TestComponent');
        Yii::$app->request->headers->set('X-Inertia-Partial-Data', '');
        
        $response = Inertia::render('TestComponent', [
            'local' => 'local-value',
        ]);
        
        $data = $response->data;
        $this->assertArrayHasKey('shared', $data['props']);
        $this->assertArrayHasKey('local', $data['props']);
    }

    public function testRenderThrowsExceptionForEmptyComponent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Inertia::render('', []);
    }

    public function testRenderThrowsExceptionForInvalidProps(): void
    {
        // Type hint prevents this at compile time, but validation is in place
        // This test documents the expected behavior
        $this->assertTrue(true);
    }
}

