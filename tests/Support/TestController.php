<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Tests\Support;

use Crenspire\Yii2Inertia\Inertia;
use yii\web\Controller;
use yii\web\Response;

class TestController extends Controller
{
    public static bool $ran = false;

    public $enableCsrfValidation = false;

    public function actionIndex(): Response
    {
        self::$ran = true;

        return Inertia::render('Test/Index', ['foo' => 'bar']);
    }

    public function actionRedirect(): Response
    {
        self::$ran = true;

        return $this->redirect(['/test/index']);
    }

    public function actionFragment(): Response
    {
        return $this->redirect(['/test/index', '#' => 'comments']);
    }

    public function actionEmpty(): void
    {
        self::$ran = true;
    }
}
