<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Props;

/**
 * An object that contributes several props at once. Add it to the props (or shared props)
 * without a key: `Inertia::render('Page', [new UserProps($user), 'other' => 1])`.
 */
interface ProvidesInertiaProperties
{
    /**
     * @return iterable<string, mixed>
     */
    public function toInertiaProperties(RenderContext $context): iterable;
}
