<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Props;

use Closure;

/**
 * A prop that is resolved once and then remembered by the client across page visits.
 */
final class OnceProp implements Onceable
{
    use ResolvesOnce;

    private Closure $callback;

    public function __construct(callable $callback)
    {
        $this->callback = Closure::fromCallable($callback);
        $this->once = true;
    }

    public function __invoke(): mixed
    {
        return ($this->callback)();
    }
}
