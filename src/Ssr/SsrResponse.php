<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Ssr;

/**
 * The result of server-side rendering a page.
 */
final class SsrResponse
{
    /**
     * @param string $head HTML to place inside `<head>` (title, meta tags, ...)
     * @param string $body HTML of the rendered application, including the root element
     */
    public function __construct(
        public readonly string $head,
        public readonly string $body,
    ) {
    }
}
