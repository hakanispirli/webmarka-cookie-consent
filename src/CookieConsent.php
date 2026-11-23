<?php

declare(strict_types=1);

namespace Webmarka\CookieConsent;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Webmarka\CookieConsent\Support\Category;
use Webmarka\CookieConsent\Support\ConsentState;
use Webmarka\CookieConsent\Support\CookieDefinition;

final class CookieConsent
{
    /**
     * Every consent type supported by Google Consent Mode v2.
     */
    public const array CONSENT_TYPES = [
        'ad_storage',
        'ad_user_data',
        'ad_personalization',
        'analytics_storage',
        'functionality_storage',
        'personalization_storage',
        'security_storage',
    ];

    /**
     * Files that may be served by the asset route.
     */
    public const array ASSETS = [
        'cookie-consent.js' => 'application/javascript; charset=UTF-8',
        'cookie-consent-alpine.js' => 'application/javascript; charset=UTF-8',
        'cookie-consent.css' => 'text/css; charset=UTF-8',
    ];

    /** @var array<string, Category>|null */
    private ?array $categories = null;

    public function __construct(
        private readonly Container $container,
        private readonly Repository $config,
    ) {}

    public function enabled(): bool
    {
        return (bool) $this->config('enabled', true);
    }

    public function config(string $key, mixed $default = null): mixed
    {
        return $this->config->get('cookie-consent.'.$key, $default);
    }

    public function policyVersion(): string
    {
        $version = $this->config('policy_version', '1');

        return is_scalar($version) ? (string) $version : '1';
    }

    public function cookieName(): string
    {
        $name = $this->config('cookie.name', 'cookie_consent');

        return is_string($name) && $name !== '' ? $name : 'cookie_consent';
    }

    /**
     * @return array<string, Category>
     */
    public function categories(): array
    {
        if ($this->categories !== null) {
            return $this->categories;
        }

        $configured = $this->config('categories', []);
        $categories = [];

        foreach (is_array($configured) ? $configured : [] as $key => $definition) {
            if (! is_string($key) || ! is_array($definition)) {
                continue;
            }

            if (preg_match('/^[a-z0-9_-]+$/', $key) !== 1) {
                throw new InvalidArgumentException("Invalid cookie consent category key [{$key}]. Use lowercase letters, digits, - and _.");
            }

            $types = array_values(array_filter(
                (array) ($definition['consent_mode'] ?? []),
                static fn (mixed $type): bool => is_string($type),
            ));

            foreach ($types as $type) {
                if (! in_array($type, self::CONSENT_TYPES, true)) {
                    throw new InvalidArgumentException("Unknown Google Consent Mode type [{$type}] in cookie consent category [{$key}].");
                }
            }

            $cookies = [];

            foreach ((array) ($definition['cookies'] ?? []) as $cookie) {
                if (is_array($cookie)) {
                    /** @var array<string, mixed> $cookie */
                    $cookies[] = CookieDefinition::fromArray($cookie);
                }
            }

            $categories[$key] = new Category(
                key: $key,
                required: (bool) ($definition['required'] ?? false),
                consentTypes: $types,
                cookies: $cookies,
                title: $this->text($definition['title'] ?? null, "categories.{$key}.title", Str::headline($key)),
                description: $this->text($definition['description'] ?? null, "categories.{$key}.description", ''),
            );
        }

        return $this->categories = $categories;
    }

    public function category(string $key): ?Category
    {
        return $this->categories()[$key] ?? null;
    }

    /**
     * The visitor's stored decision for the current policy version.
     */
    public function state(?Request $request = null): ?ConsentState
    {
        $request ??= $this->request();
        $value = $request?->cookies->get($this->cookieName());

        return ConsentState::fromCookie(is_string($value) ? $value : null, $this->policyVersion());
    }

    public function hasDecided(?Request $request = null): bool
    {
        return $this->state($request) !== null;
    }

    /**
     * Whether the visitor consented to a category. Required categories are
     * always granted. Note: with full-page caching, prefer client-side checks.
     */
    public function granted(string $category, ?Request $request = null): bool
    {
        $definition = $this->category($category);

        if ($definition === null) {
            return false;
        }

        if ($definition->required) {
            return true;
        }

        return $this->state($request)?->granted($category) ?? false;
    }

    /**
     * Consent Mode states before the visitor decides: everything denied
     * except types that only belong to required categories.
     *
     * @return array<string, 'granted'|'denied'>
     */
    public function consentModeDefaults(): array
    {
        return $this->consentModeFor([]);
    }

    /**
     * Maps category choices to Consent Mode states. A consent type is granted
     * only if every category that uses it is granted.
     *
     * @param  array<string, bool>  $choices
     * @return array<string, 'granted'|'denied'>
     */
    public function consentModeFor(array $choices): array
    {
        $states = [];

        foreach ($this->categories() as $category) {
            $granted = $category->required || ($choices[$category->key] ?? false) === true;

            foreach ($category->consentTypes as $type) {
                $states[$type] = ($states[$type] ?? true) && $granted;
            }
        }

        $result = [];

        foreach (self::CONSENT_TYPES as $type) {
            $result[$type] = ($states[$type] ?? false) ? 'granted' : 'denied';
        }

        return $result;
    }

    public function googleMode(): string
    {
        return $this->config('google.consent_mode.mode') === 'basic' ? 'basic' : 'advanced';
    }

    public function consentModeEnabled(): bool
    {
        return (bool) $this->config('google.consent_mode.enabled', true);
    }

    public function dataLayerEvent(): string
    {
        $event = $this->config('google.datalayer_event', 'cookie_consent_update');

        return is_string($event) && $event !== '' ? $event : 'cookie_consent_update';
    }

    /**
     * Region specific `gtag('consent', 'default', ...)` payloads.
     *
     * @return list<array<string, mixed>>
     */
    public function regionDefaults(): array
    {
        $regions = [];
        $defaults = $this->consentModeDefaults();

        foreach ((array) $this->config('google.consent_mode.region_defaults', []) as $override) {
            if (! is_array($override) || empty($override['region'])) {
                continue;
            }

            $payload = $defaults;

            foreach ($override as $type => $value) {
                if (in_array($type, self::CONSENT_TYPES, true) && in_array($value, ['granted', 'denied'], true)) {
                    $payload[$type] = $value;
                }
            }

            $payload['region'] = array_values(array_map('strval', (array) $override['region']));
            $regions[] = $payload;
        }

        return $regions;
    }

    /**
     * Data for the synchronous inline <head> script.
     *
     * @return array<string, mixed>
     */
    public function headConfig(): array
    {
        $map = [];
        $required = [];

        foreach ($this->categories() as $category) {
            $map[$category->key] = $category->consentTypes;
            $required[$category->key] = $category->required;
        }

        return [
            'name' => $this->cookieName(),
            'pv' => $this->policyVersion(),
            'cm' => $this->consentModeEnabled(),
            'defaults' => $this->consentModeDefaults(),
            'regions' => $this->regionDefaults(),
            'wait' => (int) $this->config('google.consent_mode.wait_for_update', 500),
            'redaction' => (bool) $this->config('google.consent_mode.ads_data_redaction', true),
            'passthrough' => (bool) $this->config('google.consent_mode.url_passthrough', false),
            'event' => $this->dataLayerEvent(),
            'map' => (object) $map,
            'req' => (object) $required,
        ];
    }

    /**
     * Configuration consumed by the JavaScript core.
     *
     * @return array<string, mixed>
     */
    public function clientConfig(): array
    {
        $secure = $this->config('cookie.secure');
        $domain = $this->config('cookie.domain');
        $loggingEnabled = $this->loggingEnabled();
        $request = $this->request();

        return [
            'cookie' => [
                'name' => $this->cookieName(),
                'lifetime' => (int) $this->config('cookie.lifetime', 365),
                'domain' => is_string($domain) && $domain !== '' ? $domain : null,
                'path' => (string) $this->config('cookie.path', '/'),
                'sameSite' => (string) $this->config('cookie.same_site', 'Lax'),
                'secure' => $secure === null ? null : (bool) $secure,
            ],
            'policyVersion' => $this->policyVersion(),
            'categories' => array_values(array_map(
                static fn (Category $category): array => $category->toClientArray(),
                $this->categories(),
            )),
            'consentMode' => [
                'enabled' => $this->consentModeEnabled(),
                'event' => $this->dataLayerEvent(),
            ],
            'log' => [
                'enabled' => $loggingEnabled,
                'url' => $loggingEnabled ? route($this->routeName('store')) : null,
                'csrfToken' => $loggingEnabled && $request?->hasSession() ? csrf_token() : null,
            ],
            'ui' => [
                'reloadOnWithdraw' => (bool) $this->config('ui.reload_on_withdraw', true),
            ],
        ];
    }

    public function loggingEnabled(): bool
    {
        return (bool) $this->config('logging.enabled', false);
    }

    public function routeName(string $name): string
    {
        $prefix = $this->config('route.name', 'cookie-consent.');

        return (is_string($prefix) ? $prefix : 'cookie-consent.').$name;
    }

    public function nonce(): ?string
    {
        $nonce = $this->config('csp_nonce');

        if (is_string($nonce) && $nonce !== '') {
            return $nonce;
        }

        $viteNonce = Vite::cspNonce();

        return is_string($viteNonce) && $viteNonce !== '' ? $viteNonce : null;
    }

    public function assetUrl(string $file): string
    {
        if (! array_key_exists($file, self::ASSETS)) {
            throw new InvalidArgumentException("Unknown cookie consent asset [{$file}].");
        }

        if ($this->config('assets.driver') === 'public') {
            return asset('vendor/cookie-consent/'.$file);
        }

        return route($this->routeName('asset'), ['file' => $file, 'v' => $this->assetVersion($file)]);
    }

    public function assetPath(string $file): string
    {
        return dirname(__DIR__).'/dist/'.$file;
    }

    public function assetVersion(string $file): string
    {
        $path = $this->assetPath($file);

        return is_file($path) ? substr((string) md5_file($path), 0, 12) : '0';
    }

    /**
     * Resolves a policy link: absolute URL, path, or "route:name".
     */
    public function url(string $key): ?string
    {
        $value = $this->config('urls.'.$key);

        if (! is_string($value) || $value === '') {
            return null;
        }

        if (str_starts_with($value, 'route:')) {
            $name = substr($value, 6);

            return Route::has($name) ? route($name) : null;
        }

        return str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, '//')
            ? $value
            : url($value);
    }

    private function request(): ?Request
    {
        if (! $this->container->bound('request')) {
            return null;
        }

        /** @var mixed $request */
        $request = $this->container->make('request');

        return $request instanceof Request ? $request : null;
    }

    private function text(mixed $configured, string $translationKey, string $fallback): string
    {
        if (is_string($configured) && $configured !== '') {
            $translated = __($configured);

            return is_string($translated) ? $translated : $configured;
        }

        $key = 'cookie-consent::cookie-consent.'.$translationKey;
        $translated = __($key);

        return is_string($translated) && $translated !== $key ? $translated : $fallback;
    }
}
