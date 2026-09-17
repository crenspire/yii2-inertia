<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Props;

use yii\web\Request;

final class RenderContext
{
    public function __construct(
        public readonly string $component,
        public readonly Request $request,
    ) {
    }
}
