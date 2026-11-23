<?php

declare(strict_types=1);

namespace Webmarka\CookieConsent\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'cookie-consent:install')]
final class InstallCommand extends Command
{
    protected $signature = 'cookie-consent:install
        {--migrations : Also publish the consent log migration}
        {--views : Also publish the Blade views}
        {--lang : Also publish the translation files}
        {--assets : Also publish the JS/CSS files to public/vendor/cookie-consent}
        {--force : Overwrite existing files}';

    protected $description = 'Publish the cookie consent configuration (and optionally migrations, views, translations, assets)';

    public function handle(): int
    {
        $tags = ['cookie-consent-config'];

        foreach (['migrations', 'views', 'lang', 'assets'] as $option) {
            if ($this->option($option)) {
                $tags[] = 'cookie-consent-'.$option;
            }
        }

        foreach ($tags as $tag) {
            $this->call('vendor:publish', ['--tag' => $tag, '--force' => (bool) $this->option('force')]);
        }

        $this->components->info('Cookie consent installed. Add this to your layout:');

        $this->line(<<<'BLADE'
            <head>
                @cookieConsentHead   {{-- before Google Tag Manager / gtag.js --}}
            </head>
            <body>
                ...
                <x-cookie-consent::banner />
                <x-cookie-consent::preferences-button />   {{-- e.g. in your footer --}}
                @cookieConsentScripts
            </body>
            BLADE);

        if (in_array('cookie-consent-migrations', $tags, true)) {
            $this->components->info('Run "php artisan migrate" and set COOKIE_CONSENT_LOGGING=true to store consent records.');
        }

        return self::SUCCESS;
    }
}
