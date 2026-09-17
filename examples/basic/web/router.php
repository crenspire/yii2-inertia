<?php

// Router for PHP's built-in web server: `php -S localhost:8080 -t web web/router.php`.
// Static files (e.g. the Vite build in web/dist) are served directly, everything else by Yii.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && is_file(__DIR__ . $path)) {
    return false;
}

require __DIR__ . '/index.php';
