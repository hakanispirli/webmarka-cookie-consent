<?php

declare(strict_types=1);

namespace Webmarka\CookieConsent\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Webmarka\CookieConsent\CookieConsentServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [CookieConsentServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.locale', 'en');
        $app['config']->set('cookie-consent.google.gtm_id', null);
        $app['config']->set('cookie-consent.google.tag_ids', []);
    }

    /**
     * @param  array<string, bool>  $categories
     */
    protected function consentCookie(array $categories, string $policyVersion = '1'): string
    {
        return (string) json_encode([
            'v' => 1,
            'id' => '6f1c1b7e-8a43-4c1e-9d6a-0d4c3c1b2a10',
            'pv' => $policyVersion,
            'c' => $categories,
            'ts' => 1_700_000_000_000,
        ]);
    }
}
