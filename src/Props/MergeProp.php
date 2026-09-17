<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Props;

/**
 * A prop that the client merges into the existing value (e.g. "load more" lists).
 */
final class MergeProp implements Mergeable, Onceable
{
    use MergesProps;
    use ResolvesOnce;

    public function __construct(private readonly mixed $value)
    {
        $this->merge = true;
    }

    public function __invoke(): mixed
    {
        return is_object($this->value) && is_callable($this->value) ? ($this->value)() : $this->value;
    }
}
