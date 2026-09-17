<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Props;

/**
 * Provides the pagination state of an infinite scroll prop.
 */
interface ProvidesScrollMetadata
{
    public function getPageName(): string;

    public function getPreviousPage(): int|string|null;

    public function getNextPage(): int|string|null;

    public function getCurrentPage(): int|string|null;
}
