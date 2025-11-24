<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia;

use Closure;
use Yii;
use yii\base\Component;
use yii\web\Request;
use yii\web\Response;

/**
 * Inertia service facade for Yii2
 * 
 * Provides a static interface to Inertia functionality, matching the
 * developer experience of inertia-laravel.
 */
class Inertia extends Component
{
    /**
     * @var array<string, mixed> Shared props available to all Inertia responses
     */
    private static array $sharedProps = [];

    /**
     * @var string|callable|null Asset version callback or string
     */
    private static $version = null;

    /**
     * @var string Root view template path
     */
    private static string $rootView = '@app/views/layouts/inertia.php';

    /**
     * Render an Inertia page
     * 
     * @param string $component The Inertia component name (e.g., 'Dashboard/Index')
     * @param array<string, mixed> $props Props to pass to the component
     * @return Response
     */
    public static function render(string $component, array $props = []): Response
    {
        // Validate component name
        if (empty($component)) {
            throw new \InvalidArgumentException('Component name cannot be empty');
        }

        // Validate props
        if (!is_array($props)) {
            throw new \InvalidArgumentException('Props must be an array');
        }

        $request = Yii::$app->request;
        $isInertiaRequest = self::isInertiaRequest($request);

        // Check for version mismatch (only for Inertia requests)
        if ($isInertiaRequest && self::hasVersionMismatch($request)) {
            return self::location($request->getUrl());
        }

        // Merge shared props
        $allProps = array_merge(self::getSharedProps(), $props);

        // Handle partial reloads
        if ($isInertiaRequest && self::isPartialReload($request)) {
            $allProps = self::filterPartialProps($allProps, $request);
        }

        if ($isInertiaRequest) {
            return InertiaResponse::json($component, $allProps, self::getVersion());
        }

        return InertiaResponse::html($component, $allProps, self::getVersion(), self::$rootView);
    }

    /**
     * Share data with all Inertia responses
     * 
     * @param string|array<string, mixed> $key Key or array of key-value pairs
     * @param mixed $value Value or closure (if key is string)
     * @return void
     */
    public static function share($key, $value = null): void
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                self::$sharedProps[$k] = $v;
            }
        } else {
            self::$sharedProps[$key] = $value;
        }
    }

    /**
     * Get all shared props (evaluating closures)
     * 
     * @return array<string, mixed>
     */
    private static function getSharedProps(): array
    {
        $props = [];
        foreach (self::$sharedProps as $key => $value) {
            $props[$key] = $value instanceof Closure ? $value() : $value;
        }
        return $props;
    }

    /**
     * Set or get the asset version
     * 
     * @param string|callable|null $version Version string or callback
     * @return string|callable|null
     */
    public static function version($version = null)
    {
        if ($version !== null) {
            self::$version = $version;
        }

        if (self::$version === null) {
            // Default: use manifest.json mtime if it exists
            try {
                $manifestPath = Yii::getAlias('@webroot/dist/manifest.json');
                if (file_exists($manifestPath)) {
                    $mtime = @filemtime($manifestPath);
                    if ($mtime !== false) {
                        return (string) $mtime;
                    }
                }
            } catch (\Exception $e) {
                // Fallback to default version if file operation fails
            }
            return '1';
        }

        if (is_callable(self::$version)) {
            try {
                return call_user_func(self::$version);
            } catch (\Exception $e) {
                // Fallback to default version if callback fails
                return '1';
            }
        }

        return self::$version;
    }

    /**
     * Create an Inertia location redirect response
     * 
     * @param string $url The URL to redirect to
     * @return Response
     */
    public static function location(string $url): Response
    {
        $request = Yii::$app->request;
        $response = Yii::$app->response;
        $response->headers->set('X-Inertia-Location', $url);
        
        // Return 409 for Inertia requests, 302 for regular requests
        if (self::isInertiaRequest($request)) {
            $response->setStatusCode(409); // Conflict status code for Inertia redirects
        } else {
            $response->setStatusCode(302);
            $response->headers->set('Location', $url);
        }
        
        return $response;
    }

    /**
     * Set the root view template
     * 
     * @param string $view View path
     * @return void
     */
    public static function setRootView(string $view): void
    {
        self::$rootView = $view;
    }

    /**
     * Get the root view template
     * 
     * @return string
     */
    public static function getRootView(): string
    {
        return self::$rootView;
    }

    /**
     * Flush shared props (useful for tests)
     * 
     * @return void
     */
    public static function flushShared(): void
    {
        self::$sharedProps = [];
    }

    /**
     * Check if the request is an Inertia request
     * 
     * @param Request $request
     * @return bool
     */
    public static function isInertiaRequest(Request $request): bool
    {
        return $request->headers->has('X-Inertia');
    }

    /**
     * Check if this is a partial reload request
     * 
     * @param Request $request
     * @return bool
     */
    private static function isPartialReload(Request $request): bool
    {
        return $request->headers->has('X-Inertia-Partial-Component') 
            && $request->headers->has('X-Inertia-Partial-Data');
    }

    /**
     * Filter props based on partial reload headers
     * 
     * @param array<string, mixed> $props
     * @param Request $request
     * @return array<string, mixed>
     */
    private static function filterPartialProps(array $props, Request $request): array
    {
        $partialData = $request->headers->get('X-Inertia-Partial-Data', '');
        
        // If partial data header is empty, return all props
        if (empty(trim($partialData))) {
            return $props;
        }
        
        $partialKeys = array_filter(array_map('trim', explode(',', $partialData)));
        
        // Always include shared props
        $sharedKeys = array_keys(self::$sharedProps);
        $allowedKeys = array_merge($sharedKeys, $partialKeys);
        
        return array_intersect_key($props, array_flip($allowedKeys));
    }

    /**
     * Check if there's a version mismatch between request and current version
     * 
     * @param Request $request
     * @return bool
     */
    private static function hasVersionMismatch(Request $request): bool
    {
        if (!$request->headers->has('X-Inertia-Version')) {
            return false;
        }

        $requestVersion = $request->headers->get('X-Inertia-Version');
        $currentVersion = self::getVersion();

        return $requestVersion !== (string) $currentVersion;
    }
}

