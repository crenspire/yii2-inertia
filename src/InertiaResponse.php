<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia;

use Yii;
use yii\web\JsonResponse;
use yii\web\Response;

/**
 * Builds Inertia responses (JSON for Inertia requests, HTML for regular requests)
 */
class InertiaResponse
{
    /**
     * Create a JSON response for Inertia requests
     * 
     * @param string $component Component name
     * @param array<string, mixed> $props Props
     * @param string $version Asset version
     * @return JsonResponse
     */
    public static function json(string $component, array $props, string $version): JsonResponse
    {
        $request = Yii::$app->request;
        
        // Build URL with query string
        $url = $request->getUrl();
        $queryString = $request->getQueryString();
        if (!empty($queryString)) {
            $url .= '?' . $queryString;
        }
        
        $payload = [
            'component' => $component,
            'props' => $props,
            'url' => $url,
            'version' => $version,
        ];

        try {
            $response = new JsonResponse($payload);
        } catch (\Exception $e) {
            // Fallback to basic response if JSON encoding fails
            $response = new JsonResponse(['error' => 'Failed to encode response'], 500);
        }
        
        $response->headers->set('X-Inertia', 'true');
        $response->headers->set('Vary', 'Accept');
        
        return $response;
    }

    /**
     * Create an HTML response with the root view
     * 
     * @param string $component Component name
     * @param array<string, mixed> $props Props
     * @param string $version Asset version
     * @param string $rootView Root view template path
     * @return Response
     */
    public static function html(string $component, array $props, string $version, string $rootView): Response
    {
        $request = Yii::$app->request;
        
        // Build URL with query string
        $url = $request->getUrl();
        $queryString = $request->getQueryString();
        if (!empty($queryString)) {
            $url .= '?' . $queryString;
        }
        
        $payload = [
            'component' => $component,
            'props' => $props,
            'url' => $url,
            'version' => $version,
        ];

        // Check if root view file exists
        $viewFile = Yii::getAlias($rootView);
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("Root view file not found: {$rootView}");
        }

        try {
            $html = Yii::$app->view->renderFile($rootView, [
                'page' => $payload,
            ]);
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to render root view: {$e->getMessage()}", 0, $e);
        }

        $response = Yii::$app->response;
        $response->data = $html;
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
        
        return $response;
    }
}

