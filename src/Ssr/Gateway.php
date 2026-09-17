<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Ssr;

/**
 * Renders an Inertia page on the server.
 */
interface Gateway
{
    /**
     * @param array<string, mixed> $page the Inertia page object
     * @return SsrResponse|null null when server-side rendering is unavailable or failed,
     * in which case the page falls back to client-side rendering
     */
    public function dispatch(array $page): ?SsrResponse;
}
