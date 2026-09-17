<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Ssr;

use RuntimeException;

/**
 * Thrown when server-side rendering fails and the gateway is configured to throw
 * (useful in end-to-end tests); otherwise failures fall back to client-side rendering.
 */
final class SsrException extends RuntimeException
{
    /**
     * @param array<string, mixed> $details the error details reported by the SSR server
     */
    public function __construct(string $message, public readonly array $details = [])
    {
        parent::__construct($message);
    }
}
