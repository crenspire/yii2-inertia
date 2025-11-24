<?php

declare(strict_types=1);

namespace app\controllers;

use Crenspire\Yii2Inertia\Inertia;
use yii\web\Controller;

class HomeController extends Controller
{
    public function actionIndex()
    {
        return Inertia::render('Home', [
            'title' => 'Welcome to Inertia.js with Yii2',
            'message' => 'This is the home page rendered with Inertia!',
        ]);
    }
}

