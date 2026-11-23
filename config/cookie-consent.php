<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Enable / disable
    |--------------------------------------------------------------------------
    |
    | When disabled, the Blade directives and components render nothing.
    |
    */

    'enabled' => (bool) env('COOKIE_CONSENT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Policy version
    |--------------------------------------------------------------------------
    |
    | Bump this value whenever your cookie list or the purposes change. Every
    | visitor whose stored consent has a different version will be asked again.
    |
    */

    'policy_version' => env('COOKIE_CONSENT_POLICY_VERSION', '1'),

    /*
    |--------------------------------------------------------------------------
    | Consent cookie
    |--------------------------------------------------------------------------
    |
    | The cookie that stores the visitor's choice. It must be readable by
    | JavaScript (not HttpOnly) and is automatically excluded from Laravel's
    | cookie encryption. "lifetime" is in days.
    |
    */

    'cookie' => [
        'name' => env('COOKIE_CONSENT_COOKIE_NAME', 'cookie_consent'),
        'lifetime' => (int) env('COOKIE_CONSENT_LIFETIME', 365),
        'domain' => env('COOKIE_CONSENT_DOMAIN'),
        'path' => '/',
        'same_site' => 'Lax',
        // null = automatically "Secure" on HTTPS pages
        'secure' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Data controller (KVKK "veri sorumlusu" / GDPR "controller")
    |--------------------------------------------------------------------------
    |
    | Shown in the preferences dialog as part of the privacy notice.
    |
    */

    'data_controller' => [
        'name' => env('COOKIE_CONSENT_CONTROLLER_NAME', env('APP_NAME')),
        'address' => env('COOKIE_CONSENT_CONTROLLER_ADDRESS'),
        'email' => env('COOKIE_CONSENT_CONTROLLER_EMAIL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Policy links
    |--------------------------------------------------------------------------
    |
    | KVKK requires the privacy notice ("aydınlatma metni") to be separate
    | from the consent request. Link it here. Values may be absolute URLs,
    | paths ("/cerez-politikasi") or route names ("route:cookie-policy").
    |
    */

    'urls' => [
        'privacy_policy' => env('COOKIE_CONSENT_PRIVACY_URL'),
        'cookie_policy' => env('COOKIE_CONSENT_COOKIE_POLICY_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    |
    | Each category maps to one or more Google Consent Mode v2 consent types.
    | Only "necessary" may be "required" (always on, no consent needed).
    | Optional categories are always off by default (no pre-ticked boxes).
    |
    | Titles and descriptions come from the translation files
    | (cookie-consent::cookie-consent.categories.{key}.*) unless you set
    | "title" / "description" here.
    |
    | Cookies: "name" is a literal cookie name, "pattern" a JavaScript regular
    | expression (without delimiters). Listed cookies are shown in the cookie
    | table and deleted from the browser when consent is withdrawn.
    | "purpose" and "duration" may be translation keys or plain strings.
    | "transfers_abroad" is the destination country (KVKK article 9), or null.
    |
    */

    'categories' => [

        'necessary' => [
            'required' => true,
            'consent_mode' => ['security_storage'],
            'cookies' => [
                [
                    'name' => env('SESSION_COOKIE', 'laravel_session'),
                    'provider' => 'first-party',
                    'purpose' => 'cookie-consent::cookie-consent.cookies.session',
                    'duration' => 'cookie-consent::cookie-consent.durations.session',
                    'transfers_abroad' => null,
                ],
                [
                    'name' => 'XSRF-TOKEN',
                    'provider' => 'first-party',
                    'purpose' => 'cookie-consent::cookie-consent.cookies.xsrf',
                    'duration' => 'cookie-consent::cookie-consent.durations.session',
                    'transfers_abroad' => null,
                ],
                [
                    'name' => 'cookie_consent',
                    'provider' => 'first-party',
                    'purpose' => 'cookie-consent::cookie-consent.cookies.consent',
                    'duration' => 'cookie-consent::cookie-consent.durations.one_year',
                    'transfers_abroad' => null,
                ],
            ],
        ],

        'functional' => [
            'required' => false,
            'consent_mode' => ['functionality_storage', 'personalization_storage'],
            'cookies' => [],
        ],

        'analytics' => [
            'required' => false,
            'consent_mode' => ['analytics_storage'],
            'cookies' => [
                [
                    'name' => '_ga',
                    'pattern' => '^_ga($|_)',
                    'provider' => 'Google LLC',
                    'purpose' => 'cookie-consent::cookie-consent.cookies.ga',
                    'duration' => 'cookie-consent::cookie-consent.durations.two_years',
                    'transfers_abroad' => 'US',
                ],
            ],
        ],

        'marketing' => [
            'required' => false,
            'consent_mode' => ['ad_storage', 'ad_user_data', 'ad_personalization'],
            'cookies' => [
                [
                    'name' => '_gcl_*',
                    'pattern' => '^_gcl_',
                    'provider' => 'Google LLC',
                    'purpose' => 'cookie-consent::cookie-consent.cookies.gcl',
                    'duration' => 'cookie-consent::cookie-consent.durations.ninety_days',
                    'transfers_abroad' => 'US',
                ],
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Google Consent Mode v2 / Google tags
    |--------------------------------------------------------------------------
    |
    | mode:
    |   "advanced" - Google tags load immediately with all consent types
    |                "denied" and send cookieless pings until consent is
    |                given (better conversion modelling for Google Ads).
    |   "basic"    - Google tags loaded by this package (gtm_id / tag_ids)
    |                are not loaded at all until analytics or marketing
    |                consent is given. More conservative.
    |
    | region_defaults lets you override defaults per region, e.g.
    |   [['region' => ['US-CA'], 'analytics_storage' => 'granted']]
    | Everything not listed falls back to "denied".
    |
    | Set gtm_id and/or tag_ids to let the package print the Google snippets
    | in the right order. Leave them empty if you add GTM / gtag yourself
    | (then place it right after @cookieConsentHead).
    |
    */

    'google' => [
        'consent_mode' => [
            'enabled' => true,
            'mode' => env('COOKIE_CONSENT_GOOGLE_MODE', 'advanced'),
            'wait_for_update' => 500,
            'ads_data_redaction' => true,
            'url_passthrough' => false,
            'region_defaults' => [],
        ],
        'gtm_id' => env('COOKIE_CONSENT_GTM_ID'),
        // e.g. ['G-XXXXXXX', 'AW-XXXXXXX']
        'tag_ids' => array_values(array_filter(explode(',', (string) env('COOKIE_CONSENT_GOOGLE_TAG_IDS', '')))),
        // Name of the dataLayer event pushed when consent is known / changes.
        'datalayer_event' => 'cookie_consent_update',
    ],

    /*
    |--------------------------------------------------------------------------
    | User interface
    |--------------------------------------------------------------------------
    |
    | layout:   "box" | "bar" | "modal"
    | position: "bottom-left" | "bottom-right" | "bottom" | "top" (box/bar)
    | theme:    "auto" | "light" | "dark"
    | styles:   false to skip the bundled CSS (e.g. when styling with Tailwind)
    | show_close_button: an "X" on the banner. Closing counts as rejecting
    |   all optional categories - closing is never treated as consent.
    | reload_on_withdraw: scripts that already ran cannot be unloaded, so the
    |   page reloads when previously granted consent is withdrawn.
    |
    */

    'ui' => [
        'layout' => 'box',
        'position' => 'bottom-left',
        'theme' => 'auto',
        'styles' => true,
        'show_close_button' => false,
        'reload_on_withdraw' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Assets
    |--------------------------------------------------------------------------
    |
    | "route"  - JS/CSS are served by the package (no publishing needed,
    |            always up to date, cached for a year with a content hash).
    | "public" - Use files published with
    |            php artisan vendor:publish --tag=cookie-consent-assets
    |
    */

    'assets' => [
        'driver' => env('COOKIE_CONSENT_ASSETS', 'route'),
        'alpine' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Consent records
    |--------------------------------------------------------------------------
    |
    | Stores every consent decision in the database so you can prove consent
    | (the burden of proof is on the data controller). Publish and run the
    | migration first: php artisan cookie-consent:install --migrations
    |
    | ip: "none" | "anonymize" (truncate) | "hash" (HMAC with APP_KEY) | "full"
    |
    */

    'logging' => [
        'enabled' => (bool) env('COOKIE_CONSENT_LOGGING', false),
        'connection' => null,
        'table' => 'consent_logs',
        'ip' => 'anonymize',
        'retention_days' => 1095,
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    */

    'route' => [
        'prefix' => 'cookie-consent',
        'name' => 'cookie-consent.',
        'middleware' => ['web'],
        'throttle' => '30,1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy nonce
    |--------------------------------------------------------------------------
    |
    | null = use Vite::cspNonce() when available.
    |
    */

    'csp_nonce' => null,

];
