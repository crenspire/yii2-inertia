<?php

declare(strict_types=1);

use Crenspire\Yii2Inertia\Inertia;
use Crenspire\Yii2Inertia\Props\ProvidesInertiaProperties;
use yii\web\Response;

if (!function_exists('inertia')) {
    /**
     * Renders an Inertia page. Shortcut for {@see Inertia::render()}.
     *
     * @param array<int|string, mixed>|ProvidesInertiaProperties $props
     * @param array<string, mixed> $viewData
     */
    function inertia(string|BackedEnum $component, array|ProvidesInertiaProperties $props = [], array $viewData = []): Response
    {
        return Inertia::render($component, $props, $viewData);
    }
}
