<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Props;

use yii\web\Request;

final class PropertyContext
{
    /**
     * @param string $key the dot-notation path of the prop
     * @param array<string, mixed> $props the sibling props
     */
    public function __construct(
        public readonly string $key,
        public readonly array $props,
        public readonly Request $request,
    ) {
    }
}
