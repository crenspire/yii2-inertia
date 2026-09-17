<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia;

use Closure;
use Crenspire\Yii2Inertia\Props\AlwaysProp;
use Crenspire\Yii2Inertia\Props\Deferrable;
use Crenspire\Yii2Inertia\Props\IgnoreFirstLoad;
use Crenspire\Yii2Inertia\Props\Mergeable;
use Crenspire\Yii2Inertia\Props\Onceable;
use Crenspire\Yii2Inertia\Props\PropertyContext;
use Crenspire\Yii2Inertia\Props\ProvidesInertiaProperties;
use Crenspire\Yii2Inertia\Props\ProvidesInertiaProperty;
use Crenspire\Yii2Inertia\Props\RenderContext;
use Crenspire\Yii2Inertia\Props\Rescuable;
use Crenspire\Yii2Inertia\Props\ScrollProp;
use JsonSerializable;
use Throwable;
use Yii;
use yii\base\Arrayable;
use yii\web\Request;

/**
 * Resolves the props of a page into the `props` of the Inertia page object and the related
 * metadata (`deferredProps`, `mergeProps`, `onceProps`, `scrollProps`, ...).
 *
 * Props are resolved recursively: special props (optional, deferred, merge, once, scroll) and
 * partial reload paths (`only` / `except`, in dot notation) work at any depth.
 */
final class PropsResolver
{
    private readonly bool $isPartial;

    private readonly bool $isInertia;

    /** @var list<string>|null */
    private readonly ?array $only;

    /** @var list<string>|null */
    private readonly ?array $except;

    /** @var list<string> */
    private readonly array $resetProps;

    /** @var list<string> */
    private readonly array $loadedOnceProps;

    /** @var array<string, list<string>> */
    private array $deferredProps = [];

    /** @var list<string> */
    private array $rescuedProps = [];

    /** @var list<string> */
    private array $mergeProps = [];

    /** @var list<string> */
    private array $prependProps = [];

    /** @var list<string> */
    private array $deepMergeProps = [];

    /** @var list<string> */
    private array $matchPropsOn = [];

    /** @var array<string, array<string, mixed>> */
    private array $scrollProps = [];

    /** @var array<string, array{prop: string, expiresAt: int|null}> */
    private array $onceProps = [];

    /** @var list<string> */
    private array $sharedPropKeys = [];

    public function __construct(
        private readonly Request $request,
        private readonly string $component,
        private readonly bool $exposeSharedPropKeys = true,
    ) {
        $this->isPartial = $request->headers->get(Header::PARTIAL_COMPONENT) === $component;
        $this->isInertia = $request->headers->has(Header::INERTIA);
        $this->only = $this->parseHeader(Header::PARTIAL_ONLY);
        $this->except = $this->parseHeader(Header::PARTIAL_EXCEPT);
        $this->resetProps = $this->parseHeader(Header::RESET) ?? [];
        $this->loadedOnceProps = $this->parseHeader(Header::EXCEPT_ONCE_PROPS) ?? [];
    }

    /**
     * @param array<int|string, mixed> $shared
     * @param array<int|string, mixed> $props
     * @return array{0: array<string, mixed>, 1: array<string, mixed>} the resolved props and the page metadata
     */
    public function resolve(array $shared, array $props): array
    {
        $props = array_merge($this->resolveSharedProps($shared), $this->resolvePropertyProviders($props));

        return [
            $this->resolveProps($this->unpackDotProps($props)),
            $this->buildMetadata(),
        ];
    }

    /**
     * @param array<int|string, mixed> $shared
     * @return array<string, mixed>
     */
    private function resolveSharedProps(array $shared): array
    {
        $resolved = $this->resolvePropertyProviders($shared);

        if ($this->exposeSharedPropKeys) {
            foreach (array_keys($resolved) as $key) {
                $this->sharedPropKeys[] = explode('.', (string) $key, 2)[0];
            }
            $this->sharedPropKeys = array_values(array_unique($this->sharedPropKeys));
        }

        return $resolved;
    }

    /**
     * @param array<int|string, mixed> $props
     * @return array<int|string, mixed>
     */
    private function resolvePropertyProviders(array $props): array
    {
        $context = null;
        $result = [];
        foreach ($props as $key => $value) {
            if (is_int($key) && $value instanceof ProvidesInertiaProperties) {
                $context ??= new RenderContext($this->component, $this->request);
                foreach ($value->toInertiaProperties($context) as $providedKey => $providedValue) {
                    $result[$providedKey] = $providedValue;
                }
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMetadata(): array
    {
        return array_filter([
            'sharedProps' => $this->sharedPropKeys,
            'mergeProps' => $this->mergeProps,
            'prependProps' => $this->prependProps,
            'deepMergeProps' => $this->deepMergeProps,
            'matchPropsOn' => $this->matchPropsOn,
            'deferredProps' => $this->deferredProps,
            'rescuedProps' => $this->rescuedProps,
            'scrollProps' => $this->scrollProps,
            'onceProps' => $this->onceProps,
        ], static fn (array $value) => $value !== []);
    }

    /**
     * @param array<int|string, mixed> $props
     * @return array<int|string, mixed>
     */
    private function resolveProps(array $props, string $prefix = '', bool $parentWasResolved = false): array
    {
        $props = $this->resolvePropertyProviders($props);
        $result = [];

        foreach ($props as $key => $prop) {
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            // Partial reloads only include matching paths. Always props and the children
            // of already resolved values bypass the filter.
            if (!$this->shouldIncludeInPartialResponse($prop, $path, $parentWasResolved)) {
                continue;
            }

            // Optional and deferred props are skipped on the initial load before evaluation.
            if (!$this->isPartial && $this->excludeFromInitialResponse($prop, $path)) {
                continue;
            }

            $value = $this->resolveValue($prop, $path, $props);
            if (in_array($path, $this->rescuedProps, true)) {
                continue;
            }

            // A closure may return a prop type; unwrap it so it takes part in filtering and metadata.
            if ($value !== $prop && $this->isPropType($value)) {
                $prop = $value;
                if (!$this->isPartial && $this->excludeFromInitialResponse($prop, $path)) {
                    continue;
                }
                $value = $this->resolveValue($prop, $path, $props);
            }

            $this->collectMetadata($prop, $path);

            // Children of values that were not arrays to begin with (e.g. a closure returning
            // an array) have been resolved as a whole and bypass partial filtering.
            $result[$key] = is_array($value)
                ? $this->resolveProps($value, $path, $parentWasResolved || !is_array($prop))
                : $value;
        }

        return $result;
    }

    private function shouldIncludeInPartialResponse(mixed $prop, string $path, bool $parentWasResolved): bool
    {
        if (!$this->isPartial || $prop instanceof AlwaysProp || $parentWasResolved) {
            return true;
        }

        if ($this->only !== null && !$this->matchesOnly($path) && !$this->leadsToOnly($path)) {
            return false;
        }

        return $this->except === null || !$this->matchesExcept($path);
    }

    private function excludeFromInitialResponse(mixed $prop, string $path): bool
    {
        if ($prop instanceof IgnoreFirstLoad) {
            if ($prop instanceof Deferrable && $prop->shouldDefer() && !$this->wasAlreadyLoadedByClient($prop, $path)) {
                $this->deferredProps[$prop->group()][] = $path;
            }
            if ($prop instanceof Mergeable && $prop->shouldMerge()) {
                $this->collectMergeableMetadata($path, $prop);
            }
            $this->collectOnceMetadata($path, $prop);

            return true;
        }

        if ($prop instanceof Deferrable && $prop->shouldDefer()) {
            $this->deferredProps[$prop->group()][] = $path;
            if ($prop instanceof Mergeable && $prop->shouldMerge()) {
                $this->collectMergeableMetadata($path, $prop);
            }

            return true;
        }

        // Once props the client already has are not sent again on Inertia visits.
        if ($this->isInertia && $this->wasAlreadyLoadedByClient($prop, $path)) {
            $this->collectOnceMetadata($path, $prop);

            return true;
        }

        return false;
    }

    private function wasAlreadyLoadedByClient(mixed $prop, string $path): bool
    {
        return $prop instanceof Onceable
            && $prop->shouldResolveOnce()
            && !$prop->shouldBeRefreshed()
            && in_array($prop->getKey() ?? $path, $this->loadedOnceProps, true);
    }

    /**
     * @param array<int|string, mixed> $siblings
     */
    private function resolveValue(mixed $value, string $path, array $siblings): mixed
    {
        if ($value instanceof ScrollProp) {
            $value->configureMergeIntent($this->request);
        }

        $shouldRescue = $value instanceof Rescuable && $value->shouldRescue();

        try {
            if (is_object($value) && is_callable($value)) {
                $value = $value();
            }
            if ($value instanceof ProvidesInertiaProperty) {
                $value = $value->toInertiaProperty(new PropertyContext($path, $siblings, $this->request));
            }
            if ($value instanceof Arrayable) {
                $value = $value->toArray();
            }
            if ($value instanceof JsonSerializable) {
                $value = $value->jsonSerialize();
            }

            return $value;
        } catch (Throwable $e) {
            if (!$shouldRescue) {
                throw $e;
            }
            Yii::error($e, __METHOD__);
            $this->rescuedProps[] = $path;

            return null;
        }
    }

    private function isPropType(mixed $value): bool
    {
        return $value instanceof AlwaysProp
            || $value instanceof Deferrable
            || $value instanceof IgnoreFirstLoad
            || $value instanceof Mergeable
            || $value instanceof Onceable;
    }

    private function collectMetadata(mixed $prop, string $path): void
    {
        if ($prop instanceof Mergeable && $prop->shouldMerge()) {
            $this->collectMergeableMetadata($path, $prop);
        }
        if ($prop instanceof ScrollProp) {
            $this->scrollProps[$path] = $prop->metadata() + ['reset' => in_array($path, $this->resetProps, true)];
        }
        $this->collectOnceMetadata($path, $prop);
    }

    private function collectMergeableMetadata(string $path, Mergeable $prop): void
    {
        if (in_array($path, $this->resetProps, true)) {
            return;
        }
        if ($this->isPartial && !$this->isIncludedInPartialMetadata($path)) {
            return;
        }

        if ($prop->shouldDeepMerge()) {
            $this->deepMergeProps[] = $path;
        } elseif ($prop->appendsAtRoot()) {
            $this->mergeProps[] = $path;
        } elseif ($prop->prependsAtRoot()) {
            $this->prependProps[] = $path;
        } else {
            foreach ($prop->appendsAtPaths() as $appendPath) {
                $this->mergeProps[] = "{$path}.{$appendPath}";
            }
            foreach ($prop->prependsAtPaths() as $prependPath) {
                $this->prependProps[] = "{$path}.{$prependPath}";
            }
        }

        foreach ($prop->matchesOn() as $strategy) {
            $this->matchPropsOn[] = "{$path}.{$strategy}";
        }
    }

    private function collectOnceMetadata(string $path, mixed $prop): void
    {
        if (!$prop instanceof Onceable || !$prop->shouldResolveOnce()) {
            return;
        }
        if ($this->isPartial && !$this->isIncludedInPartialMetadata($path)) {
            return;
        }

        $this->onceProps[$prop->getKey() ?? $path] = [
            'prop' => $path,
            'expiresAt' => $prop->expiresAt(),
        ];
    }

    private function isIncludedInPartialMetadata(string $path): bool
    {
        if ($this->only !== null && !$this->matchesOnly($path)) {
            return false;
        }

        return $this->except === null || !$this->matchesExcept($path);
    }

    private function matchesOnly(string $path): bool
    {
        foreach ($this->only ?? [] as $onlyPath) {
            if ($path === $onlyPath || str_starts_with($path, "{$onlyPath}.")) {
                return true;
            }
        }

        return false;
    }

    private function leadsToOnly(string $path): bool
    {
        foreach ($this->only ?? [] as $onlyPath) {
            if (str_starts_with($onlyPath, "{$path}.")) {
                return true;
            }
        }

        return false;
    }

    private function matchesExcept(string $path): bool
    {
        foreach ($this->except ?? [] as $exceptPath) {
            if ($path === $exceptPath || str_starts_with($path, "{$exceptPath}.")) {
                return true;
            }
        }

        return false;
    }

    /**
     * Expands props with dot-notation keys (e.g. `'auth.user' => ...`) into nested arrays.
     *
     * @param array<int|string, mixed> $props
     * @return array<int|string, mixed>
     */
    private function unpackDotProps(array $props): array
    {
        foreach ($props as $key => $value) {
            if (!is_string($key) || !str_contains($key, '.')) {
                continue;
            }

            if ($value instanceof Closure) {
                $value = $value();
            }
            if ($value instanceof Arrayable) {
                $value = $value->toArray();
            }

            unset($props[$key]);
            $this->ensurePathIsTraversable($props, $key);
            self::setPath($props, $key, $value);
        }

        return $props;
    }

    /**
     * @param array<int|string, mixed> $props
     */
    private function ensurePathIsTraversable(array &$props, string $dotKey): void
    {
        $segments = explode('.', $dotKey);
        array_pop($segments);

        $current = &$props;
        foreach ($segments as $segment) {
            if (!isset($current[$segment])) {
                return;
            }
            if ($current[$segment] instanceof Closure) {
                $current[$segment] = $current[$segment]();
            }
            if ($current[$segment] instanceof Arrayable) {
                $current[$segment] = $current[$segment]->toArray();
            }
            if (!is_array($current[$segment])) {
                return;
            }
            $current = &$current[$segment];
        }
    }

    /**
     * @param array<int|string, mixed> $array
     */
    private static function setPath(array &$array, string $path, mixed $value): void
    {
        $segments = explode('.', $path);
        $last = array_pop($segments);

        $current = &$array;
        foreach ($segments as $segment) {
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }
        $current[$last] = $value;
    }

    /**
     * @return list<string>|null
     */
    private function parseHeader(string $header): ?array
    {
        $values = array_values(array_filter(
            array_map('trim', explode(',', (string) $this->request->headers->get($header, ''))),
            static fn (string $value) => $value !== '',
        ));

        return $values === [] ? null : $values;
    }
}
