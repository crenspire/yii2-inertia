<?php

declare(strict_types=1);

namespace app\controllers;

use Crenspire\Yii2Inertia\Inertia;
use yii\data\ArrayDataProvider;
use yii\web\Controller;
use yii\web\Response;

class FeedController extends Controller
{
    public function actionIndex(): Response
    {
        $posts = array_map(
            static fn (int $id) => ['id' => $id, 'title' => "Post #{$id}", 'body' => 'Loaded page by page with <InfiniteScroll>.'],
            range(1, 60),
        );

        return Inertia::render('Feed', [
            // The data provider is converted to { data: [...] } with the pagination metadata.
            'posts' => Inertia::scroll(fn () => new ArrayDataProvider([
                'allModels' => $posts,
                'pagination' => ['pageSize' => 15],
                'sort' => false,
            ])),
        ]);
    }
}
