<?php

// Minimal stand-in for the Inertia SSR server, used by HttpGatewayTest.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
header('Content-Type: application/json');

if ($path === '/health') {
    echo '{"status":"OK"}';

    return;
}

$page = json_decode((string) file_get_contents('php://input'), true);

if (in_array($path, ['/render', '/__inertia_ssr'], true) && ($page['component'] ?? '') === 'Broken') {
    http_response_code(500);
    echo json_encode(['error' => 'window is not defined', 'type' => 'browser-api', 'hint' => 'Use onMounted', 'sourceLocation' => 'src/pages/Broken.jsx:3:1']);

    return;
}

if (in_array($path, ['/render', '/__inertia_ssr'], true)) {
    echo json_encode([
        'head' => ['<title>' . $page['component'] . '</title>'],
        'body' => '<div id="app" data-server-rendered="true">' . $path . ':' . $page['props']['name'] . '</div>',
    ]);

    return;
}

http_response_code(404);
