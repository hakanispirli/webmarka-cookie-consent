<?php

declare(strict_types=1);

namespace Webmarka\CookieConsent\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class LoggingTestCase extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('cookie-consent.logging.enabled', true);
        $app['config']->set('database.default', 'testing');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__).'/database/migrations');
    }
}
