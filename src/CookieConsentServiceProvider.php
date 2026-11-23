<?php

declare(strict_types=1);

namespace Webmarka\CookieConsent;

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Webmarka\CookieConsent\Console\ExportCommand;
use Webmarka\CookieConsent\Console\InstallCommand;
use Webmarka\CookieConsent\Console\PruneCommand;

final class CookieConsentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/cookie-consent.php', 'cookie-consent');

        // Scoped: resolved once per request (safe for Octane / queues).
        $this->app->scoped(CookieConsent::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'cookie-consent');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'cookie-consent');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'cookie-consent');

        $this->excludeConsentCookieFromEncryption();
        $this->registerBladeDirectives();

        if ($this->app->runningInConsole()) {
            $this->registerPublishing();

            $this->commands([
                InstallCommand::class,
                ExportCommand::class,
                PruneCommand::class,
            ]);
        }
    }

    /**
     * The consent cookie is written and read by JavaScript, so Laravel
     * must not encrypt it.
     */
    private function excludeConsentCookieFromEncryption(): void
    {
        $name = $this->app->make('config')->get('cookie-consent.cookie.name', 'cookie_consent');

        if (is_string($name) && $name !== '') {
            EncryptCookies::except($name);
        }
    }

    private function registerBladeDirectives(): void
    {
        Blade::directive('cookieConsentHead', static fn (): string => "<?php echo view('cookie-consent::head')->render(); ?>");

        Blade::directive('cookieConsentScripts', static fn (): string => "<?php echo view('cookie-consent::scripts')->render(); ?>");

        // @consent('analytics') ... @else ... @endconsent
        Blade::if('consent', static fn (string $category): bool => app(CookieConsent::class)->granted($category));
    }

    private function registerPublishing(): void
    {
        $this->publishes([
            __DIR__.'/../config/cookie-consent.php' => config_path('cookie-consent.php'),
        ], 'cookie-consent-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/cookie-consent'),
        ], 'cookie-consent-views');

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/cookie-consent'),
        ], 'cookie-consent-lang');

        $this->publishes([
            __DIR__.'/../dist/cookie-consent.js' => public_path('vendor/cookie-consent/cookie-consent.js'),
            __DIR__.'/../dist/cookie-consent-alpine.js' => public_path('vendor/cookie-consent/cookie-consent-alpine.js'),
            __DIR__.'/../dist/cookie-consent.css' => public_path('vendor/cookie-consent/cookie-consent.css'),
        ], 'cookie-consent-assets');

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'cookie-consent-migrations');
    }
}
