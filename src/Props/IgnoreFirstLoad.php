<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Props;

/**
 * Marks a prop that is left out of the initial page load and only resolved
 * when explicitly requested by a partial reload.
 */
interface IgnoreFirstLoad
{
}
