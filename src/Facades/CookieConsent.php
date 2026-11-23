<?php

declare(strict_types=1);

namespace Webmarka\CookieConsent\Facades;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Webmarka\CookieConsent\Support\Category;
use Webmarka\CookieConsent\Support\ConsentState;

/**
 * @method static bool enabled()
 * @method static string policyVersion()
 * @method static string cookieName()
 * @method static array<string, Category> categories()
 * @method static Category|null category(string $key)
 * @method static ConsentState|null state(?Request $request = null)
 * @method static bool hasDecided(?Request $request = null)
 * @method static bool granted(string $category, ?Request $request = null)
 * @method static array<string, 'granted'|'denied'> consentModeDefaults()
 * @method static array<string, 'granted'|'denied'> consentModeFor(array<string, bool> $choices)
 * @method static string googleMode()
 * @method static array<string, mixed> clientConfig()
 * @method static string|null url(string $key)
 *
 * @see \Webmarka\CookieConsent\CookieConsent
 */
final class CookieConsent extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Webmarka\CookieConsent\CookieConsent::class;
    }
}
