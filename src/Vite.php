<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia;

use InvalidArgumentException;
use Yii;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\web\View;

/**
 * Renders the `<script>` / `<link>` tags for a Vite application, either from the build
 * manifest (production) or from a running Vite dev server (development, with HMR).
 *
 * ```php
 * <?= Inertia::vite()->tags('src/main.jsx') ?>
 * ```
 *
 * @see https://vite.dev/guide/backend-integration
 */
class Vite extends BaseObject
{
    /**
     * @var string Directory Vite builds into (`build.outDir`).
     */
    public string $buildPath = '@webroot/dist';

    /**
     * @var string Public URL of [[buildPath]].
     */
    public string $baseUrl = '@web/dist';

    /**
     * @var list<string> Manifest locations relative to [[buildPath]], checked in order.
     * Vite 5+ writes `.vite/manifest.json`, older versions `manifest.json`.
     */
    public array $manifestFiles = ['.vite/manifest.json', 'manifest.json'];

    /**
     * @var string|null URL of the Vite dev server (e.g. `http://localhost:5173`).
     * When set, assets are served by the dev server instead of the build manifest.
     */
    public ?string $devServerUrl = null;

    /**
     * @var bool Whether to inject the React Fast Refresh preamble in dev server mode
     * (required by `@vitejs/plugin-react`).
     */
    public bool $reactRefresh = false;

    /**
     * @var array<string, array<string, mixed>>|null
     */
    private ?array $manifest = null;

    public function isRunningHot(): bool
    {
        return $this->devServerUrl !== null && $this->devServerUrl !== '';
    }

    /**
     * Returns the HTML tags for the given entry points.
     *
     * @param string|list<string> $entries entry points as given to `build.rollupOptions.input`,
     * relative to the Vite root (e.g. `src/main.jsx`)
     */
    public function tags(string|array $entries): string
    {
        $entries = (array) $entries;

        return $this->isRunningHot() ? $this->devTags($entries) : $this->buildTags($entries);
    }

    /**
     * Returns the public URL of a file processed by Vite (e.g. an image imported by an entry point).
     */
    public function asset(string $path): string
    {
        if ($this->isRunningHot()) {
            return $this->devUrl($path);
        }

        return $this->buildUrl($this->chunk($path)['file']);
    }

    /**
     * Returns a hash of the build manifest, or null when no manifest exists.
     * Used as the default Inertia asset version.
     */
    public function manifestHash(): ?string
    {
        $path = $this->manifestPath();
        if ($path === null) {
            return null;
        }
        $hash = md5_file($path);

        return $hash === false ? null : $hash;
    }

    /**
     * @param list<string> $entries
     */
    private function devTags(array $entries): string
    {
        $tags = [];
        if ($this->reactRefresh) {
            $refreshUrl = json_encode($this->devUrl('@react-refresh'), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            $tags[] = $this->script(
                "import RefreshRuntime from {$refreshUrl};\n"
                . "RefreshRuntime.injectIntoGlobalHook(window);\n"
                . "window.\$RefreshReg\$ = () => {};\n"
                . "window.\$RefreshSig\$ = () => (type) => type;\n"
                . 'window.__vite_plugin_react_preamble_installed__ = true;',
                ['type' => 'module'],
            );
        }
        $tags[] = $this->script('', ['type' => 'module', 'src' => $this->devUrl('@vite/client')]);

        foreach ($entries as $entry) {
            $tags[] = $this->isCss($entry)
                ? Html::cssFile($this->devUrl($entry))
                : $this->script('', ['type' => 'module', 'src' => $this->devUrl($entry)]);
        }

        return implode("\n", $tags);
    }

    /**
     * @param list<string> $entries
     */
    private function buildTags(array $entries): string
    {
        $preloads = [];
        $styles = [];
        $scripts = [];

        foreach ($entries as $entry) {
            $chunk = $this->chunk($entry);
            if ($this->isCss($chunk['file'])) {
                $styles[$chunk['file']] = true;
                continue;
            }
            $scripts[$chunk['file']] = true;
            $seen = [];
            $this->collectDependencies($entry, true, $styles, $preloads, $seen);
        }

        $tags = [];
        foreach (array_keys($preloads) as $file) {
            $tags[] = Html::tag('link', '', ['rel' => 'modulepreload', 'href' => $this->buildUrl($file)]);
        }
        foreach (array_keys($styles) as $file) {
            $tags[] = Html::cssFile($this->buildUrl($file));
        }
        foreach (array_keys($scripts) as $file) {
            $tags[] = $this->script('', ['type' => 'module', 'src' => $this->buildUrl($file)]);
        }

        return implode("\n", $tags);
    }

    /**
     * @param array<string, true> $styles
     * @param array<string, true> $preloads
     * @param array<string, true> $seen
     */
    private function collectDependencies(string $name, bool $isEntry, array &$styles, array &$preloads, array &$seen): void
    {
        if (isset($seen[$name])) {
            return;
        }
        $seen[$name] = true;

        $chunk = $this->chunk($name);
        if (!$isEntry) {
            $preloads[$chunk['file']] = true;
        }
        foreach ($chunk['css'] ?? [] as $file) {
            $styles[$file] = true;
        }
        foreach ($chunk['imports'] ?? [] as $import) {
            $this->collectDependencies($import, false, $styles, $preloads, $seen);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function chunk(string $name): array
    {
        $manifest = $this->manifest();
        if (!isset($manifest[$name]['file'])) {
            throw new InvalidArgumentException("Unable to locate \"{$name}\" in the Vite manifest.");
        }

        return $manifest[$name];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function manifest(): array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }

        $path = $this->manifestPath();
        if ($path === null) {
            throw new InvalidConfigException(
                'Vite manifest not found in ' . Yii::getAlias($this->buildPath)
                . '. Run "vite build" (with build.manifest enabled) or configure devServerUrl.'
            );
        }

        $manifest = json_decode((string) file_get_contents($path), true);
        if (!is_array($manifest)) {
            throw new InvalidConfigException("Vite manifest {$path} is not valid JSON.");
        }

        return $this->manifest = $manifest;
    }

    private function manifestPath(): ?string
    {
        $buildPath = rtrim((string) Yii::getAlias($this->buildPath), '/');
        foreach ($this->manifestFiles as $file) {
            if (is_file("{$buildPath}/{$file}")) {
                return "{$buildPath}/{$file}";
            }
        }

        return null;
    }

    private function buildUrl(string $file): string
    {
        return rtrim((string) Yii::getAlias($this->baseUrl), '/') . '/' . $file;
    }

    private function devUrl(string $path): string
    {
        return rtrim((string) $this->devServerUrl, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Renders a script tag, applying the view's `scriptOptions` (e.g. a CSP nonce) like {@see Html::script()}.
     *
     * @param array<string, mixed> $options
     */
    private function script(string $content, array $options): string
    {
        $view = Yii::$app?->getView();
        if ($view instanceof View && !empty($view->scriptOptions)) {
            $options = array_merge($view->scriptOptions, $options);
        }

        return Html::tag('script', $content, $options);
    }

    private function isCss(string $path): bool
    {
        return (bool) preg_match('/\.(css|less|sass|scss|styl|stylus|pcss|postcss)$/', $path);
    }
}
