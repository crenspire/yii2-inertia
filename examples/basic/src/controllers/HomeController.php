<?php

declare(strict_types=1);

namespace app\controllers;

use Crenspire\Yii2Inertia\Inertia;
use yii\web\Controller;
use yii\web\Response;

class HomeController extends Controller
{
    public function actionIndex(): Response
    {
        return Inertia::render('Home', [
            'message' => 'This page is rendered by a Yii2 controller and a React component.',
            'phpVersion' => PHP_VERSION,
            'yiiVersion' => \Yii::getVersion(),
        ]);
    }
}
