<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Props;

use Closure;
use Crenspire\Yii2Inertia\Header;
use InvalidArgumentException;
use yii\data\DataProviderInterface;
use yii\web\Request;

/**
 * A paginated prop for the `<InfiniteScroll>` component. Each page of items is merged into the
 * items already loaded by the client.
 *
 * The value (or the value returned by a closure) is typically a paginated {@see DataProviderInterface},
 * which is converted to `['data' => $models]` with pagination metadata taken from the provider.
 */
final class ScrollProp implements Deferrable, Mergeable
{
    use DefersProps;
    use MergesProps;

    private mixed $raw = null;

    private mixed $resolved = null;

    private bool $isResolved = false;

    /**
     * @param string $wrapper the key holding the list of items inside the prop value
     * @param ProvidesScrollMetadata|callable|null $metadata pagination metadata, or a callback receiving
     * the raw value and returning it; derived from the data provider when null
     */
    public function __construct(
        private readonly mixed $value,
        private readonly string $wrapper = 'data',
        private readonly mixed $metadata = null,
    ) {
        $this->merge = true;
    }

    public function configureMergeIntent(Request $request): static
    {
        return $request->headers->get(Header::INFINITE_SCROLL_MERGE_INTENT) === 'prepend'
            ? $this->prepend($this->wrapper)
            : $this->append($this->wrapper);
    }

    /**
     * @return array{pageName: string, previousPage: int|string|null, nextPage: int|string|null, currentPage: int|string|null}
     */
    public function metadata(): array
    {
        $provider = $this->resolveMetadataProvider();

        return [
            'pageName' => $provider->getPageName(),
            'previousPage' => $provider->getPreviousPage(),
            'nextPage' => $provider->getNextPage(),
            'currentPage' => $provider->getCurrentPage(),
        ];
    }

    public function __invoke(): mixed
    {
        if (!$this->isResolved) {
            $this->raw = $this->value instanceof Closure ? ($this->value)() : $this->value;
            $this->resolved = $this->raw instanceof DataProviderInterface
                ? [$this->wrapper => array_values($this->raw->getModels())]
                : $this->raw;
            $this->isResolved = true;
        }

        return $this->resolved;
    }

    private function resolveMetadataProvider(): ProvidesScrollMetadata
    {
        if ($this->metadata instanceof ProvidesScrollMetadata) {
            return $this->metadata;
        }

        $this();

        if ($this->metadata !== null) {
            $provider = ($this->metadata)($this->raw);
            if (!$provider instanceof ProvidesScrollMetadata) {
                throw new InvalidArgumentException('The scroll metadata callback must return a ' . ProvidesScrollMetadata::class . '.');
            }

            return $provider;
        }

        if ($this->raw instanceof DataProviderInterface) {
            return ScrollMetadata::fromDataProvider($this->raw);
        }

        throw new InvalidArgumentException(
            'The scroll prop value is not a data provider. Pass a metadata callback or a ' . ProvidesScrollMetadata::class . '.'
        );
    }
}
