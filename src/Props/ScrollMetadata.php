<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Props;

use InvalidArgumentException;
use yii\data\DataProviderInterface;
use yii\data\Pagination;

/**
 * Pagination state of an infinite scroll prop.
 */
final class ScrollMetadata implements ProvidesScrollMetadata
{
    public function __construct(
        private readonly string $pageName,
        private readonly int|string|null $previousPage = null,
        private readonly int|string|null $nextPage = null,
        private readonly int|string|null $currentPage = null,
    ) {
    }

    public static function fromPagination(Pagination $pagination): self
    {
        $current = $pagination->getPage() + 1;

        return new self(
            $pagination->pageParam,
            $current > 1 ? $current - 1 : null,
            $current < $pagination->getPageCount() ? $current + 1 : null,
            $current,
        );
    }

    public static function fromDataProvider(DataProviderInterface $dataProvider): self
    {
        $dataProvider->prepare();
        $pagination = $dataProvider->getPagination();
        if (!$pagination instanceof Pagination) {
            throw new InvalidArgumentException('Infinite scroll requires a data provider with pagination enabled.');
        }

        return self::fromPagination($pagination);
    }

    public function getPageName(): string
    {
        return $this->pageName;
    }

    public function getPreviousPage(): int|string|null
    {
        return $this->previousPage;
    }

    public function getNextPage(): int|string|null
    {
        return $this->nextPage;
    }

    public function getCurrentPage(): int|string|null
    {
        return $this->currentPage;
    }
}
