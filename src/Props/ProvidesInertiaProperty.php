<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Props;

/**
 * An object that computes its own prop value.
 */
interface ProvidesInertiaProperty
{
    public function toInertiaProperty(PropertyContext $prop): mixed;
}
