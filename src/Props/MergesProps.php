<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Props;

/**
 * Default implementation of {@see Mergeable}.
 */
trait MergesProps
{
    protected bool $merge = false;

    protected bool $deepMerge = false;

    /** @var list<string> */
    protected array $matchOn = [];

    protected bool $append = true;

    /** @var list<string> */
    protected array $appendsAtPaths = [];

    /** @var list<string> */
    protected array $prependsAtPaths = [];

    public function merge(): static
    {
        $this->merge = true;

        return $this;
    }

    public function deepMerge(): static
    {
        $this->deepMerge = true;

        return $this->merge();
    }

    /**
     * Sets the key(s) used to match items when merging (e.g. `id`), so existing items are updated in place.
     *
     * @param string|list<string> $matchOn
     */
    public function matchOn(string|array $matchOn): static
    {
        $this->matchOn = array_values((array) $matchOn);

        return $this;
    }

    /**
     * Appends incoming items: at the root (`true`), at a nested path (`'data'`), or at several paths
     * (`['data', 'meta.items' => 'id']`, where the value is the key to match items on).
     *
     * @param bool|string|array<int|string, string> $path
     */
    public function append(bool|string|array $path = true, ?string $matchOn = null): static
    {
        return $this->addMergePath($path, $matchOn, true);
    }

    /**
     * Prepends incoming items. Accepts the same arguments as {@see append()}.
     *
     * @param bool|string|array<int|string, string> $path
     */
    public function prepend(bool|string|array $path = true, ?string $matchOn = null): static
    {
        return $this->addMergePath($path, $matchOn, false);
    }

    public function shouldMerge(): bool
    {
        return $this->merge;
    }

    public function shouldDeepMerge(): bool
    {
        return $this->deepMerge;
    }

    public function matchesOn(): array
    {
        return $this->matchOn;
    }

    public function appendsAtRoot(): bool
    {
        return $this->append && $this->mergesAtRoot();
    }

    public function prependsAtRoot(): bool
    {
        return !$this->append && $this->mergesAtRoot();
    }

    public function appendsAtPaths(): array
    {
        return $this->appendsAtPaths;
    }

    public function prependsAtPaths(): array
    {
        return $this->prependsAtPaths;
    }

    protected function mergesAtRoot(): bool
    {
        return $this->appendsAtPaths === [] && $this->prependsAtPaths === [];
    }

    /**
     * @param bool|string|array<int|string, string> $path
     */
    private function addMergePath(bool|string|array $path, ?string $matchOn, bool $append): static
    {
        if (is_bool($path)) {
            $this->append = $append ? $path : !$path;
        } elseif (is_string($path)) {
            if ($append) {
                $this->appendsAtPaths[] = $path;
            } else {
                $this->prependsAtPaths[] = $path;
            }
            if ($matchOn !== null && $matchOn !== '') {
                $this->matchOn[] = "{$path}.{$matchOn}";
            }
        } else {
            foreach ($path as $key => $value) {
                is_int($key)
                    ? $this->addMergePath($value, null, $append)
                    : $this->addMergePath($key, $value, $append);
            }
        }

        return $this;
    }
}
