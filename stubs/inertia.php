<?php

/**
 * Root view for Inertia pages. Copy it to views/layouts/inertia.php (the default `rootView`).
 *
 * @var yii\web\View $this
 * @var array $page the Inertia page object
 * @var Crenspire\Yii2Inertia\Ssr\SsrResponse|null $ssr the server-side rendered page, if SSR is enabled
 */

use Crenspire\Yii2Inertia\Inertia;
use yii\helpers\Html;

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= Html::encode(Yii::$app->language) ?>">
<head>
    <meta charset="<?= Html::encode(Yii::$app->charset) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title data-inertia><?= Html::encode(Yii::$app->name) ?></title>
    <?= Inertia::vite()->tags('src/main.js') ?>
    <?= Inertia::ssrHead($ssr) ?>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
<?= Inertia::app($page, $ssr) ?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
