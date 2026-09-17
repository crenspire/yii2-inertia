<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia;

use yii\base\BootstrapInterface;
use yii\web\Application as WebApplication;

/**
 * Registered through `extra.bootstrap` in composer.json, so the `inertia` component hooks into
 * every web application without having to be listed in the application's `bootstrap` config.
 */
final class Bootstrap implements BootstrapInterface
{
    public function bootstrap($app): void
    {
        if ($app instanceof WebApplication) {
            Inertia::getManager()->bootstrap($app);
        }
    }
}
