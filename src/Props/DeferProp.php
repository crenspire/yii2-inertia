<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Props;

use Closure;

/**
 * A prop that the client loads in a separate request right after the page has rendered.
 * Props sharing a group are fetched together.
 */
final class DeferProp implements Deferrable, IgnoreFirstLoad, Mergeable, Onceable, Rescuable
{
    use DefersProps;
    use MergesProps;
    use ResolvesOnce;

    private Closure $callback;

    /**
     * @param bool $rescue whether to omit the prop (instead of failing the request) when the callback throws
     */
    public function __construct(callable $callback, ?string $group = null, private readonly bool $rescue = false)
    {
        $this->callback = Closure::fromCallable($callback);
        $this->defer($group);
    }

    public function shouldRescue(): bool
    {
        return $this->rescue;
    }

    public function __invoke(): mixed
    {
        return ($this->callback)();
    }
}
