<?php

use Crenspire\Yii2Inertia\Manager;

$params = require __DIR__ . '/params.php';

return [
    'id' => 'yii2-inertia-example',
    'name' => $params['appName'],
    'basePath' => dirname(__DIR__),
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            // Change this to a random string in a real application.
            'cookieValidationKey' => 'yii2-inertia-example-not-secret',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '' => 'home/index',
                'dashboard' => 'dashboard/index',
                'GET contact' => 'contact/index',
                'POST contact' => 'contact/store',
                'feed' => 'feed/index',
            ],
        ],
        // The component is registered and bootstrapped automatically; configure it here.
        'inertia' => [
            'class' => Manager::class,
            'rootView' => '@app/views/layouts/inertia.php',
            'shared' => [
                'appName' => $params['appName'],
            ],
            'vite' => [
                // Start the dev server with `VITE_DEV_SERVER=http://localhost:5173 php -S ...` for HMR.
                'devServerUrl' => getenv('VITE_DEV_SERVER') ?: null,
                'reactRefresh' => true,
            ],
        ],
    ],
    'params' => $params,
];
