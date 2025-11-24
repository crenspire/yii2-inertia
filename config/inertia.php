<?php

declare(strict_types=1);

/**
 * Default Inertia configuration for Yii2
 * 
 * You can override these values in your application configuration
 * by merging this config or setting values directly.
 */

return [
    // Root view template path
    'root_view' => '@app/views/layouts/inertia.php',
    
    // Asset version callback or string
    // Default: uses manifest.json mtime if it exists, otherwise '1'
    'version' => null,
    
    // Shared props (can be set via Inertia::share())
    'shared' => [],
];

