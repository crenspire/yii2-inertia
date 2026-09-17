<?php

declare(strict_types=1);

namespace app\controllers;

use Crenspire\Yii2Inertia\Inertia;
use yii\web\Controller;
use yii\web\Response;

class DashboardController extends Controller
{
    public function actionIndex(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'users' => 1234,
                'revenue' => 56789,
                'orders' => 890,
            ],
            // Re-evaluated on partial reloads: router.reload({ only: ['generatedAt'] })
            'generatedAt' => fn () => date('H:i:s'),
            // Loaded by the client in a second request after the page has rendered.
            'activity' => Inertia::defer(function () {
                usleep(600_000);

                return [
                    ['id' => 1, 'text' => 'Ann placed an order'],
                    ['id' => 2, 'text' => 'Bob signed up'],
                    ['id' => 3, 'text' => 'Chloe left a review'],
                ];
            }),
        ]);
    }
}
