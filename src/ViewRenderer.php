<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia;

use Yii;
use yii\base\ViewRenderer;
use yii\web\Response;

/**
 * Yii2 ViewRenderer for Inertia
 * 
 * This renderer can be registered in the view component configuration:
 * 
 * ```php
 * 'view' => [
 *     'renderers' => [
 *         'inertia' => Crenspire\Yii2Inertia\ViewRenderer::class,
 *     ],
 * ],
 * ```
 * 
 * Then in controllers, you can use:
 * ```php
 * return $this->render('inertia:component-name', ['props' => [...]]);
 * ```
 */
class ViewRenderer extends ViewRenderer
{
    /**
     * @var string Root view template path
     */
    public string $rootView = '@app/views/layouts/inertia.php';

    /**
     * Renders a view file
     * 
     * @param \yii\base\View $view The view object used for rendering the file
     * @param string $file The view file
     * @param array $params The parameters to be passed to the view file
     * @return string The rendering result
     */
    public function render($view, $file, $params)
    {
        // Extract component name from file path
        // Format: 'inertia:ComponentName' or 'inertia:Pages/Dashboard'
        $component = $params['component'] ?? $this->extractComponentName($file);
        $props = $params['props'] ?? [];

        // Use Inertia facade to render
        $response = Inertia::render($component, $props);
        
        // If it's already a response, return its content
        if ($response instanceof Response) {
            return $response->data;
        }

        return '';
    }

    /**
     * Extract component name from file path
     * 
     * @param string $file
     * @return string
     */
    private function extractComponentName(string $file): string
    {
        // Remove extension and convert path to component name
        $name = basename($file, '.php');
        $dir = dirname($file);
        $basePath = Yii::getAlias('@app');
        
        if (strpos($dir, $basePath) === 0) {
            $relative = substr($dir, strlen($basePath));
            $parts = array_filter(explode('/', $relative));
            if (!empty($parts)) {
                return implode('/', $parts) . '/' . $name;
            }
        }
        
        return $name;
    }
}

