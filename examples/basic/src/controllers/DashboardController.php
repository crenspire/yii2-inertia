<?php

declare(strict_types=1);

namespace app\controllers;

use Crenspire\Yii2Inertia\Inertia;
use yii\web\Controller;

class DashboardController extends Controller
{
    public function actionIndex()
    {
        return Inertia::render('Dashboard', [
            'title' => 'Dashboard',
            'stats' => [
                'users' => 1234,
                'revenue' => 56789,
                'orders' => 890,
            ],
        ]);
    }
}

