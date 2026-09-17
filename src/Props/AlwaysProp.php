<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Props;

/**
 * A prop that is always included, even in partial reloads that did not ask for it.
 */
final class AlwaysProp
{
    public function __construct(private readonly mixed $value)
    {
    }

    public function __invoke(): mixed
    {
        return is_object($this->value) && is_callable($this->value) ? ($this->value)() : $this->value;
    }
}
