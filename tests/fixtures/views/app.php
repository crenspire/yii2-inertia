<?php

use Crenspire\Yii2Inertia\Inertia;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var array $page */
/** @var Crenspire\Yii2Inertia\Ssr\SsrResponse|null $ssr */
/** @var string|null $title */
?>
<!DOCTYPE html>
<html>
<head>
<title><?= Html::encode($title ?? 'Test') ?></title>
<?= Inertia::ssrHead($ssr) ?>
</head>
<body>
<?= Inertia::app($page, $ssr) ?>
</body>
</html>
