<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia;

use yii\web\Response;

/**
 * Global helper function for Inertia
 * 
 * Provides the same API as Inertia::render() but as a global function
 * for better developer ergonomics.
 * 
 * @param string $component The Inertia component name
 * @param array<string, mixed> $props Props to pass to the component
 * @return Response
 */
function inertia(string $component, array $props = []): Response
{
    return Inertia::render($component, $props);
}

