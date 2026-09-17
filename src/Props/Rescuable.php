<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Props;

/**
 * A prop whose resolution errors are reported and swallowed instead of failing the response.
 * Rescued props are omitted and listed in the page's `rescuedProps`.
 */
interface Rescuable
{
    public function shouldRescue(): bool;
}
