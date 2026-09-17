<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Props;

/**
 * Default implementation of {@see Deferrable}.
 */
trait DefersProps
{
    protected bool $deferred = false;

    protected ?string $deferGroup = null;

    public function defer(?string $group = null): static
    {
        $this->deferred = true;
        $this->deferGroup = $group;

        return $this;
    }

    public function shouldDefer(): bool
    {
        return $this->deferred;
    }

    public function group(): string
    {
        return $this->deferGroup ?? 'default';
    }
}
