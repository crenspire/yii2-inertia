<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Ssr;

use Yii;
use yii\base\BaseObject;

/**
 * Renders pages through the Inertia SSR server.
 *
 * In production this is the server built from your SSR entry point (`node dist/ssr.js`, listening on
 * port 13714 by default). When the Vite dev server is used, pages are rendered by the `@inertiajs/vite`
 * plugin through its `/__inertia_ssr` endpoint instead, with hot module replacement.
 */
class HttpGateway extends BaseObject implements Gateway
{
    /**
     * @var string Base URL of the production SSR server.
     */
    public string $url = 'http://127.0.0.1:13714';

    /**
     * @var string|null URL of the Vite dev server. When set, pages are rendered through its
     * `/__inertia_ssr` endpoint. The `inertia` component fills this in from its Vite configuration.
     */
    public ?string $devServerUrl = null;

    /**
     * @var string|null Path (or alias) of the SSR bundle. When set and the file does not exist,
     * SSR is skipped without contacting the server (unless the dev server is used).
     */
    public ?string $bundle = null;

    /**
     * @var float Seconds to wait for the SSR server.
     */
    public float $timeout = 5.0;

    /**
     * @var bool Whether to throw an {@see SsrException} instead of falling back to client-side
     * rendering when rendering fails.
     */
    public bool $throwOnError = false;

    public function dispatch(array $page): ?SsrResponse
    {
        $isHot = $this->devServerUrl !== null && $this->devServerUrl !== '';
        if (!$isHot && $this->bundle !== null && !is_file((string) Yii::getAlias($this->bundle))) {
            return null;
        }

        $url = $isHot
            ? rtrim((string) $this->devServerUrl, '/') . '/__inertia_ssr'
            : rtrim($this->url, '/') . '/render';

        [$status, $body] = $this->post($url, json_encode($page, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

        if ($status === null) {
            $this->handleFailure($page, ['error' => "The SSR server at {$url} is not reachable.", 'type' => 'connection']);

            return null;
        }

        $data = json_decode($body, true);
        if ($status >= 400) {
            $this->handleFailure($page, is_array($data) ? $data : ['error' => "The SSR server responded with HTTP {$status}."]);

            return null;
        }
        if (!is_array($data) || !isset($data['body']) || !is_string($data['body'])) {
            $this->handleFailure($page, ['error' => 'The SSR server returned an invalid response.']);

            return null;
        }

        $head = $data['head'] ?? [];

        return new SsrResponse(
            is_array($head) ? implode("\n", array_map('strval', $head)) : (string) $head,
            $data['body'],
        );
    }

    /**
     * Checks whether the production SSR server is running.
     */
    public function isHealthy(): bool
    {
        [$status] = $this->request(rtrim($this->url, '/') . '/health', 'GET');

        return $status !== null && $status >= 200 && $status < 300;
    }

    /**
     * @return array{0: int|null, 1: string} the HTTP status (null when the server is unreachable) and body
     */
    protected function post(string $url, string $json): array
    {
        return $this->request($url, 'POST', $json);
    }

    /**
     * @return array{0: int|null, 1: string}
     */
    protected function request(string $url, string $method, ?string $content = null): array
    {
        $options = [
            'method' => $method,
            'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
            'timeout' => $this->timeout,
            'ignore_errors' => true,
        ];
        if ($content !== null) {
            $options['content'] = $content;
        }

        $stream = @fopen($url, 'rb', false, stream_context_create(['http' => $options]));
        if ($stream === false) {
            return [null, ''];
        }
        try {
            $body = (string) stream_get_contents($stream);
            $headers = stream_get_meta_data($stream)['wrapper_data'] ?? [];
        } finally {
            fclose($stream);
        }

        $status = 200;
        foreach (is_array($headers) ? $headers : [] as $header) {
            if (is_string($header) && preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $matches)) {
                $status = (int) $matches[1];
            }
        }

        return [$status, $body];
    }

    /**
     * @param array<string, mixed> $page
     * @param array<string, mixed> $error
     */
    protected function handleFailure(array $page, array $error): void
    {
        $message = sprintf(
            'Inertia SSR failed for component "%s": %s',
            (string) ($page['component'] ?? ''),
            (string) ($error['error'] ?? 'Unknown SSR error'),
        );
        if (isset($error['hint'])) {
            $message .= "\nHint: " . $error['hint'];
        }
        if (isset($error['sourceLocation'])) {
            $message .= "\nAt: " . $error['sourceLocation'];
        }

        if ($this->throwOnError) {
            throw new SsrException($message, $error);
        }

        Yii::error($message, __METHOD__);
    }
}
