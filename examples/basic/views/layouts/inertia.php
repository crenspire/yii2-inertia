<?php

use Crenspire\Yii2Inertia\Inertia;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var array $page */
/** @var Crenspire\Yii2Inertia\Ssr\SsrResponse|null $ssr */

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= Html::encode(Yii::$app->language) ?>" class="h-full">
<head>
    <meta charset="<?= Html::encode(Yii::$app->charset) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title data-inertia><?= Html::encode(Yii::$app->name) ?></title>
    <?= Inertia::vite()->tags('src/main.jsx') ?>
    <?= Inertia::ssrHead($ssr) ?>
    <?php $this->head() ?>
</head>
<body class="h-full bg-slate-50 text-slate-900 antialiased">
<?php $this->beginBody() ?>
<?= Inertia::app($page, $ssr) ?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
