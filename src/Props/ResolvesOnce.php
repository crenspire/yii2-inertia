<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Props;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * Default implementation of {@see Onceable}.
 */
trait ResolvesOnce
{
    protected bool $once = false;

    protected bool $refresh = false;

    protected ?int $ttl = null;

    protected ?string $key = null;

    public function once(bool $value = true, ?string $as = null, DateTimeInterface|DateInterval|int|null $until = null): static
    {
        $this->once = $value;
        if ($as !== null) {
            $this->as($as);
        }
        if ($until !== null) {
            $this->until($until);
        }

        return $this;
    }

    public function shouldResolveOnce(): bool
    {
        return $this->once;
    }

    public function shouldBeRefreshed(): bool
    {
        return $this->refresh;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * Stores the prop on the client under a custom key, so it can be shared between pages using different prop names.
     */
    public function as(\BackedEnum|\UnitEnum|string $key): static
    {
        $this->key = match (true) {
            $key instanceof \BackedEnum => (string) $key->value,
            $key instanceof \UnitEnum => $key->name,
            default => $key,
        };

        return $this;
    }

    /**
     * Sends the value again even if the client already has it.
     */
    public function fresh(bool $value = true): static
    {
        $this->refresh = $value;

        return $this;
    }

    /**
     * @param DateTimeInterface|DateInterval|int $delay an expiry date, an interval or a number of seconds
     */
    public function until(DateTimeInterface|DateInterval|int $delay): static
    {
        $now = time();
        $this->ttl = match (true) {
            $delay instanceof DateTimeInterface => max(0, $delay->getTimestamp() - $now),
            $delay instanceof DateInterval => max(0, (new DateTimeImmutable('@' . $now))->add($delay)->getTimestamp() - $now),
            default => $delay,
        };

        return $this;
    }

    public function expiresAt(): ?int
    {
        return $this->ttl === null ? null : (time() + $this->ttl) * 1000;
    }
}
