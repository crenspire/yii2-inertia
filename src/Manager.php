<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia;

use Closure;
use Crenspire\Yii2Inertia\Props\AlwaysProp;
use Crenspire\Yii2Inertia\Props\OnceProp;
use Crenspire\Yii2Inertia\Props\ProvidesInertiaProperties;
use Crenspire\Yii2Inertia\Ssr\Gateway;
use Crenspire\Yii2Inertia\Ssr\HttpGateway;
use Crenspire\Yii2Inertia\Ssr\SsrResponse;
use InvalidArgumentException;
use stdClass;
use Yii;
use yii\base\ActionEvent;
use yii\base\BootstrapInterface;
use yii\base\Component;
use yii\base\Event;
use yii\base\Model;
use yii\base\Module;
use yii\di\Instance;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\Application as WebApplication;
use yii\web\JsonParser;
use yii\web\Request;
use yii\web\Response;
use yii\web\Session;

/**
 * The Inertia application component, implementing the server side of the Inertia.js v3 protocol.
 *
 * It is registered automatically as the `inertia` component. Configure it in your application config:
 *
 * ```php
 * 'components' => [
 *     'inertia' => [
 *         'class' => \Crenspire\Yii2Inertia\Manager::class,
 *         'rootView' => '@app/views/layouts/inertia.php',
 *         'shared' => [
 *             'appName' => 'My App',
 *             'auth.user' => fn () => Yii::$app->user->identity?->toArray(['id', 'name']),
 *         ],
 *         'vite' => ['devServerUrl' => YII_ENV_DEV ? 'http://localhost:5173' : null],
 *     ],
 * ],
 * ```
 *
 * When bootstrapped it hooks into the request lifecycle to implement the parts of the protocol that
 * apply to every response: asset version checks, `Vary: X-Inertia`, redirects for Inertia (XHR)
 * requests, JSON request bodies and CSRF protection for the Inertia HTTP client.
 */
class Manager extends Component implements BootstrapInterface
{
    private const ERRORS_FLASH = '__inertia_errors';
    private const FLASH_DATA_KEY = '__inertia.flash_data';
    private const CLEAR_HISTORY_KEY = '__inertia.clear_history';
    private const PRESERVE_FRAGMENT_KEY = '__inertia.preserve_fragment';

    /**
     * @var string View file rendered for the initial (non-Inertia) page load. It receives the
     * `$page` (array) and `$ssr` ({@see SsrResponse}|null) variables plus the view data passed to render().
     */
    public string $rootView = '@app/views/layouts/inertia.php';

    /**
     * @var string|int|Closure|null The current asset version. When null, a hash of the Vite
     * manifest is used (or an empty string when there is no manifest).
     */
    public string|int|Closure|null $version = null;

    /**
     * @var array<int|string, mixed> Props shared with every page. Closures are evaluated lazily,
     * dot-notation keys (`auth.user`) create nested props.
     */
    public array $shared = [];

    /**
     * @var bool Whether to encrypt the browser history state of every page.
     */
    public bool $encryptHistory = false;

    /**
     * @var bool Whether to expose all validation messages of an attribute instead of the first one.
     */
    public bool $withAllErrors = false;

    /**
     * @var bool Whether to list the shared prop keys in the page object (`sharedProps`), which lets the
     * client carry them over during client-side visits.
     */
    public bool $exposeSharedPropKeys = true;

    /**
     * @var Closure|null Resolves the page URL, `fn (Request $request): string`. Defaults to the request URI.
     */
    public ?Closure $urlResolver = null;

    /**
     * @var bool Whether to expose the CSRF token in a JavaScript-readable cookie and accept it
     * back from the header the Inertia HTTP client sends automatically.
     */
    public bool $enableCsrfCookie = true;

    public string $csrfCookieName = 'XSRF-TOKEN';

    public string $csrfHeaderName = 'X-XSRF-TOKEN';

    /**
     * @var bool Whether to register {@see JsonParser} for `application/json` request bodies (the
     * format Inertia forms are submitted in) when the request component has no parser for it.
     */
    public bool $registerJsonParser = true;

    /**
     * @var bool|Closure Whether to render the initial page load through the SSR server.
     * A closure receives the request and returns a boolean.
     */
    public bool|Closure $ssrEnabled = false;

    /**
     * @var list<string> URL paths (without leading slash, `*` wildcards allowed) that are never server-side rendered.
     */
    public array $ssrExcept = [];

    /**
     * @var array<string, mixed>|string|Gateway The SSR gateway, as an object, component ID or configuration array.
     */
    public array|string|Gateway $ssrGateway = ['class' => HttpGateway::class];

    /**
     * @var array<string, mixed>|Vite The Vite helper, as an object or configuration array.
     */
    public array|Vite $vite = [];

    private bool $bootstrapped = false;

    private bool $rendered = false;

    public function bootstrap($app): void
    {
        if ($this->bootstrapped || !$app instanceof WebApplication) {
            return;
        }
        $this->bootstrapped = true;

        // Inertia's HTTP client submits forms as JSON; make them available through Request::post().
        $request = $app->getRequest();
        if ($this->registerJsonParser && !isset($request->parsers['application/json'])) {
            $request->parsers['application/json'] = JsonParser::class;
        }

        $app->on(WebApplication::EVENT_BEFORE_REQUEST, [$this, 'handleBeforeRequest']);
        $app->on(Module::EVENT_BEFORE_ACTION, [$this, 'handleBeforeAction']);
        $app->getResponse()->on(Response::EVENT_BEFORE_SEND, [$this, 'handleBeforeSend']);
    }

    /**
     * Renders an Inertia page: a JSON page object for Inertia requests, the root view otherwise.
     *
     * @param string|\BackedEnum $component the page component name (e.g. `Users/Index`)
     * @param array<int|string, mixed>|ProvidesInertiaProperties $props
     * @param array<string, mixed> $viewData extra variables passed to the root view
     */
    public function render(string|\BackedEnum $component, array|ProvidesInertiaProperties $props = [], array $viewData = []): Response
    {
        $component = $component instanceof \BackedEnum ? (string) $component->value : $component;
        if ($component === '') {
            throw new InvalidArgumentException('The Inertia component name cannot be empty.');
        }

        $request = $this->getRequest();
        $response = Yii::$app->getResponse();
        $page = $this->createPage($component, $props, $request);
        $this->rendered = true;

        self::addVary($response);

        if ($this->isInertiaRequest($request)) {
            $response->format = Response::FORMAT_RAW;
            $response->data = $this->encodePage($page);
            $response->headers->set('Content-Type', 'application/json; charset=UTF-8');
            $response->headers->set(Header::INERTIA, 'true');

            return $response;
        }

        $ssr = $this->shouldRenderOnServer($request) ? $this->getSsrGateway()->dispatch($page) : null;

        $response->format = Response::FORMAT_HTML;
        $response->data = Yii::$app->getView()->renderFile(
            $this->rootView,
            array_merge($viewData, ['page' => $page, 'ssr' => $ssr]),
        );

        return $response;
    }

    /**
     * Builds the Inertia page object.
     *
     * @param array<int|string, mixed>|ProvidesInertiaProperties $props
     * @return array<string, mixed>
     */
    public function createPage(string $component, array|ProvidesInertiaProperties $props = [], ?Request $request = null): array
    {
        $request ??= $this->getRequest();
        $props = is_array($props) ? $props : [$props];

        $shared = array_merge(
            ['errors' => new AlwaysProp(fn () => $this->resolveErrors($request))],
            $this->shared,
        );

        [$resolvedProps, $metadata] = (new PropsResolver($request, $component, $this->exposeSharedPropKeys))
            ->resolve($shared, $props);

        $page = array_merge(
            [
                'component' => $component,
                'props' => $resolvedProps === [] ? new stdClass() : $resolvedProps,
                'url' => $this->urlResolver !== null ? ($this->urlResolver)($request) : $request->getUrl(),
                'version' => $this->getVersion(),
            ],
            $metadata,
        );

        if ($this->pullSession(self::CLEAR_HISTORY_KEY)) {
            $page['clearHistory'] = true;
        }
        if ($this->encryptHistory) {
            $page['encryptHistory'] = true;
        }
        $flash = $this->pullSession(self::FLASH_DATA_KEY);
        if (is_array($flash) && $flash !== []) {
            $page['flash'] = $flash;
        }
        if ($this->pullSession(self::PRESERVE_FRAGMENT_KEY)) {
            $page['preserveFragment'] = true;
        }

        return $page;
    }

    /**
     * Encodes a page object as JSON. The output is safe to embed in a `<script>` element.
     *
     * @param array<string, mixed> $page
     * @throws \JsonException when the page contains values that cannot be encoded (e.g. invalid UTF-8)
     */
    public function encodePage(array $page): string
    {
        return json_encode($page, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    }

    /**
     * Shares a prop (or an array of props, or a props provider) with every page.
     */
    public function share(string|array|ProvidesInertiaProperties $key, mixed $value = null): void
    {
        if (is_array($key)) {
            $this->shared = array_merge($this->shared, $key);
        } elseif ($key instanceof ProvidesInertiaProperties) {
            $this->shared[] = $key;
        } else {
            $this->shared[$key] = $value;
        }
    }

    /**
     * Shares a prop that the client loads once and then remembers across pages.
     */
    public function shareOnce(string $key, callable $callback): OnceProp
    {
        $prop = new OnceProp($callback);
        $this->share($key, $prop);

        return $prop;
    }

    /**
     * Returns a shared prop (unevaluated, dot notation supported), or all shared props when no key is given.
     */
    public function getShared(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->shared : ArrayHelper::getValue($this->shared, $key, $default);
    }

    public function flushShared(): void
    {
        $this->shared = [];
    }

    public function getVersion(): string
    {
        $version = $this->version instanceof Closure ? ($this->version)() : $this->version;

        if ($version === null) {
            return $this->getVite()->manifestHash() ?? '';
        }

        return (string) $version;
    }

    /**
     * Redirects to a URL with a full page visit. For Inertia requests this is a `409 Conflict`
     * with an `X-Inertia-Location` header, which makes the client navigate with `window.location`.
     * Use it for external URLs and non-Inertia pages; use a normal redirect for Inertia pages.
     *
     * @param string|array<int|string, mixed> $url a URL or a route accepted by {@see Url::to()}
     */
    public function location(string|array $url): Response
    {
        $url = Url::to($url);
        $response = Yii::$app->getResponse();

        if ($this->isInertiaRequest()) {
            $response->data = null;
            $response->content = null;
            $response->headers->set(Header::LOCATION, $url);
            $response->setStatusCode(409);

            return $response;
        }

        return $response->redirect($url, 302, false);
    }

    /**
     * Redirects back to the previous page (the referrer), using `303 See Other` after PUT, PATCH and DELETE requests.
     *
     * @param string|array<int|string, mixed> $fallback URL or route used when there is no referrer
     */
    public function back(string|array $fallback = ['/']): Response
    {
        $request = $this->getRequest();
        $url = $request->getReferrer() ?? Url::to($fallback);
        $status = in_array($request->getMethod(), ['PUT', 'PATCH', 'DELETE'], true) ? 303 : 302;

        return Yii::$app->getResponse()->redirect($url, $status, false);
    }

    /**
     * Flashes validation errors to the session. They are exposed to the next rendered page as the
     * `errors` prop, which is what `useForm()` and `<Form>` read.
     *
     * @param Model|array<string, string|list<string>> $errors a model with errors, or attribute => message(s)
     * @param string $bag the error bag name (`errorBag` option of the visit)
     */
    public function withErrors(Model|array $errors, string $bag = 'default'): void
    {
        $messages = $errors instanceof Model ? $errors->getErrors() : $errors;
        $messages = array_map(static fn ($message) => array_values(array_map('strval', (array) $message)), $messages);

        $session = Yii::$app->getSession();
        $bags = $session->get(self::ERRORS_FLASH, []);
        $bags[$bag] = array_replace($bags[$bag] ?? [], $messages);
        $session->setFlash(self::ERRORS_FLASH, $bags);
    }

    /**
     * Flashes data to the next rendered page. The client exposes it as `page.flash` and fires a
     * `flash` event; unlike props, flash data is not stored in the browser history.
     *
     * @param string|array<string, mixed> $key
     */
    public function flash(string|array $key, mixed $value = null): void
    {
        $flash = is_array($key) ? $key : [$key => $value];
        $session = Yii::$app->getSession();
        $session->set(self::FLASH_DATA_KEY, array_merge($session->get(self::FLASH_DATA_KEY, []), $flash));
    }

    /**
     * @return array<string, mixed> the data flashed for the next rendered page
     */
    public function getFlashed(): array
    {
        return (array) $this->readSession(self::FLASH_DATA_KEY, []);
    }

    /**
     * Clears the encrypted browser history on the next rendered page (e.g. after logout).
     */
    public function clearHistory(): void
    {
        Yii::$app->getSession()->set(self::CLEAR_HISTORY_KEY, true);
    }

    /**
     * Keeps the URL fragment (`#section`) of the original visit when it ends in a redirect.
     */
    public function preserveFragment(): void
    {
        Yii::$app->getSession()->set(self::PRESERVE_FRAGMENT_KEY, true);
    }

    public function isInertiaRequest(?Request $request = null): bool
    {
        $request ??= Yii::$app->getRequest();

        return $request instanceof Request && $request->headers->has(Header::INERTIA);
    }

    public function getVite(): Vite
    {
        if (!$this->vite instanceof Vite) {
            $this->vite = Yii::createObject(array_merge(['class' => Vite::class], $this->vite));
        }

        return $this->vite;
    }

    public function getSsrGateway(): Gateway
    {
        if (!$this->ssrGateway instanceof Gateway) {
            $gateway = Instance::ensure($this->ssrGateway, Gateway::class);
            if ($gateway instanceof HttpGateway && $gateway->devServerUrl === null && $this->getVite()->isRunningHot()) {
                $gateway->devServerUrl = $this->getVite()->devServerUrl;
            }
            $this->ssrGateway = $gateway;
        }

        return $this->ssrGateway;
    }

    /**
     * Accepts the CSRF token from the header sent by the Inertia HTTP client.
     *
     * @internal event handler
     */
    public function handleBeforeRequest(): void
    {
        $request = Yii::$app->getRequest();
        if (!$this->enableCsrfCookie || !$request instanceof Request) {
            return;
        }

        $token = $request->headers->get($this->csrfHeaderName);
        if ($token !== null && !$request->headers->has($request->csrfHeader)) {
            $request->headers->set($request->csrfHeader, $token);
        }
    }

    /**
     * Forces a full page reload when the client's asset version is outdated.
     *
     * @internal event handler
     */
    public function handleBeforeAction(ActionEvent $event): void
    {
        $request = Yii::$app->getRequest();
        if (!$this->isInertiaRequest($request) || $request->getMethod() !== 'GET') {
            return;
        }

        $version = $this->getVersion();
        if ((string) $request->headers->get(Header::VERSION, '') === $version) {
            return;
        }

        $event->isValid = false;
        $this->location($request->getAbsoluteUrl())->headers->set(Header::VERSION, $version);
    }

    /**
     * @internal event handler
     */
    public function handleBeforeSend(Event $event): void
    {
        /** @var Response $response */
        $response = $event->sender;
        $request = Yii::$app->getRequest();
        if (!$request instanceof Request) {
            return;
        }

        self::addVary($response);

        $isInertia = $this->isInertiaRequest($request);
        if ($isInertia) {
            $this->adjustInertiaResponse($request, $response);
        }

        if ($this->enableCsrfCookie && ($isInertia || $this->rendered) && $request->enableCsrfValidation) {
            $this->sendCsrfCookie($request->getCsrfToken(), $request);
        }
    }

    /**
     * Sends the JavaScript-readable CSRF cookie. A native cookie is used because Yii's response
     * cookies are signed when cookie validation is enabled, which would make the token unusable.
     */
    protected function sendCsrfCookie(string $token, Request $request): void
    {
        if (headers_sent()) {
            return;
        }

        $options = $request->csrfCookie;
        setcookie($this->csrfCookieName, $token, [
            'expires' => 0,
            'path' => $options['path'] ?? '/',
            'domain' => $options['domain'] ?? '',
            'secure' => $options['secure'] ?? $request->getIsSecureConnection(),
            'httponly' => false,
            'samesite' => $options['sameSite'] ?? 'Lax',
        ]);
    }

    private function adjustInertiaResponse(Request $request, Response $response): void
    {
        $headers = $response->getHeaders();

        // Response::redirect() replaces the Location header with X-Redirect for AJAX requests,
        // which the Inertia client (an XHR) cannot follow.
        if ($headers->has('X-Redirect') && !$headers->has('Location')) {
            $headers->set('Location', $headers->get('X-Redirect'));
            $headers->remove('X-Redirect');
        }

        // An action that returned nothing redirects back, e.g. after a form submission.
        if (
            $response->getStatusCode() === 200
            && in_array($response->data, [null, ''], true)
            && in_array($response->content, [null, ''], true)
            && $response->stream === null
        ) {
            $this->back();
        }

        // A 302 after PUT/PATCH/DELETE would make the browser repeat the request with the same method.
        if ($response->getStatusCode() === 302 && in_array($request->getMethod(), ['PUT', 'PATCH', 'DELETE'], true)) {
            $response->setStatusCode(303);
        }

        // An XHR cannot see the fragment of a redirect target, so the client is told to visit it instead.
        $location = (string) $headers->get('Location', '');
        if (
            $response->getIsRedirection()
            && str_contains($location, '#')
            && $request->headers->get(Header::PURPOSE) !== 'prefetch'
        ) {
            $headers->remove('Location');
            $headers->set(Header::REDIRECT, $location);
            $response->setStatusCode(409);
        }
    }

    private function shouldRenderOnServer(Request $request): bool
    {
        $enabled = $this->ssrEnabled instanceof Closure ? (bool) ($this->ssrEnabled)($request) : $this->ssrEnabled;
        if (!$enabled) {
            return false;
        }

        $path = ltrim((string) parse_url($request->getUrl(), PHP_URL_PATH), '/');
        foreach ($this->ssrExcept as $pattern) {
            if (fnmatch(ltrim($pattern, '/'), $path)) {
                return false;
            }
        }

        return true;
    }

    private function resolveErrors(Request $request): stdClass
    {
        $bags = $this->readFlash(self::ERRORS_FLASH, []);
        if (!is_array($bags) || $bags === []) {
            return new stdClass();
        }

        $bags = array_map(
            fn (array $bag) => (object) array_map(fn (array $messages) => $this->withAllErrors ? $messages : $messages[0], $bag),
            $bags,
        );

        $errorBag = $request->headers->get(Header::ERROR_BAG);
        if (isset($bags['default']) && is_string($errorBag) && $errorBag !== '') {
            return (object) [$errorBag => $bags['default']];
        }

        return $bags['default'] ?? (object) $bags;
    }

    /**
     * Reads a flash message without starting a session for visitors that do not have one.
     */
    private function readFlash(string $key, mixed $default): mixed
    {
        $session = $this->getExistingSession();

        return $session === null ? $default : $session->getFlash($key, $default);
    }

    private function readSession(string $key, mixed $default): mixed
    {
        $session = $this->getExistingSession();

        return $session === null ? $default : $session->get($key, $default);
    }

    private function pullSession(string $key): mixed
    {
        $session = $this->getExistingSession();

        return $session?->has($key) ? $session->remove($key) : null;
    }

    private function getExistingSession(): ?Session
    {
        if (!Yii::$app->has('session')) {
            return null;
        }
        $session = Yii::$app->getSession();

        return $session instanceof Session && ($session->getIsActive() || $session->getHasSessionId()) ? $session : null;
    }

    private function getRequest(): Request
    {
        $request = Yii::$app->getRequest();
        if (!$request instanceof Request) {
            throw new InvalidArgumentException('Inertia responses can only be rendered for web requests.');
        }

        return $request;
    }

    private static function addVary(Response $response): void
    {
        $headers = $response->getHeaders();
        $vary = array_filter(array_map('trim', explode(',', (string) $headers->get('Vary', ''))));
        foreach ($vary as $value) {
            if (strcasecmp($value, Header::INERTIA) === 0) {
                return;
            }
        }
        $vary[] = Header::INERTIA;
        $headers->set('Vary', implode(', ', $vary));
    }
}
