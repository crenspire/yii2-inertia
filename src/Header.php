<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia;

/**
 * HTTP headers used by the Inertia.js protocol.
 *
 * @see https://inertiajs.com/the-protocol
 */
final class Header
{
    public const INERTIA = 'X-Inertia';
    public const VERSION = 'X-Inertia-Version';
    public const LOCATION = 'X-Inertia-Location';
    public const PARTIAL_COMPONENT = 'X-Inertia-Partial-Component';
    public const PARTIAL_ONLY = 'X-Inertia-Partial-Data';
    public const PARTIAL_EXCEPT = 'X-Inertia-Partial-Except';
    public const RESET = 'X-Inertia-Reset';
    public const ERROR_BAG = 'X-Inertia-Error-Bag';
    public const REDIRECT = 'X-Inertia-Redirect';
    public const INFINITE_SCROLL_MERGE_INTENT = 'X-Inertia-Infinite-Scroll-Merge-Intent';
    public const EXCEPT_ONCE_PROPS = 'X-Inertia-Except-Once-Props';
    public const PURPOSE = 'Purpose';

    private function __construct()
    {
    }
}
