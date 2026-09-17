<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Props;

use DateInterval;
use DateTimeInterface;

/**
 * A prop that the client remembers after it was loaded once and does not request again
 * (across pages) until it expires.
 */
interface Onceable
{
    public function once(bool $value = true): static;

    public function shouldResolveOnce(): bool;

    public function shouldBeRefreshed(): bool;

    public function getKey(): ?string;

    public function as(\BackedEnum|\UnitEnum|string $key): static;

    public function until(DateTimeInterface|DateInterval|int $delay): static;

    /**
     * @return int|null expiry as a Unix timestamp in milliseconds
     */
    public function expiresAt(): ?int;
}
