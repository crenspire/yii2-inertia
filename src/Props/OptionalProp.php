<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Props;

use Closure;

/**
 * A prop that is never included on the first page load and only evaluated
 * when requested via a partial reload (`router.reload({ only: ['key'] })`).
 */
final class OptionalProp implements IgnoreFirstLoad, Onceable
{
    use ResolvesOnce;

    private Closure $callback;

    public function __construct(callable $callback)
    {
        $this->callback = Closure::fromCallable($callback);
    }

    public function __invoke(): mixed
    {
        return ($this->callback)();
    }
}
