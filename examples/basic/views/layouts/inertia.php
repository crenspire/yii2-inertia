<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inertia.js App</title>
    <script type="module" crossorigin src="/dist/assets/index.js"></script>
    <link rel="stylesheet" crossorigin href="/dist/assets/index.css">
</head>
<body>
    <div id="app" data-page="<?= htmlspecialchars(json_encode($page), ENT_QUOTES, 'UTF-8') ?>"></div>
</body>
</html>

