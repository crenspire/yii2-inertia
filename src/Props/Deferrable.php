<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Props;

/**
 * A prop that can be loaded by the client in a separate request after the page has rendered.
 */
interface Deferrable
{
    public function shouldDefer(): bool;

    public function group(): string;
}
