<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Tests\Unit;

use Crenspire\Yii2Inertia\InertiaResponse;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\web\Application;
use yii\web\Request;
use yii\web\Response;

class InertiaResponseTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        Yii::$app = null;
        parent::tearDown();
    }

    public function testJsonResponse(): void
    {
        $response = InertiaResponse::json('TestComponent', ['prop' => 'value'], '1.0');
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::FORMAT_JSON, $response->format);
        $this->assertEquals('true', $response->headers->get('X-Inertia'));
        
        $data = $response->data;
        $this->assertEquals('TestComponent', $data['component']);
        $this->assertEquals(['prop' => 'value'], $data['props']);
        $this->assertEquals('1.0', $data['version']);
    }

    public function testHtmlResponse(): void
    {
        // Mock view renderer
        $view = Yii::$app->view;
        $view->renderers = [];
        
        $response = InertiaResponse::html('TestComponent', ['prop' => 'value'], '1.0', '@app/views/test.php');
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::FORMAT_RAW, $response->format);
    }
}

