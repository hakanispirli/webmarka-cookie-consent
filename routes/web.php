<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Webmarka\CookieConsent\CookieConsent;
use Webmarka\CookieConsent\Http\Controllers\AssetController;
use Webmarka\CookieConsent\Http\Controllers\ConsentLogController;

$cookieConsent = app(CookieConsent::class);

if (! $cookieConsent->enabled()) {
    return;
}

$prefix = (string) $cookieConsent->config('route.prefix', 'cookie-consent');
$name = (string) $cookieConsent->config('route.name', 'cookie-consent.');

// Static assets: no session, no cookies.
Route::prefix($prefix)
    ->name($name)
    ->group(static function (): void {
        Route::get('assets/{file}', AssetController::class)
            ->where('file', implode('|', array_map(preg_quote(...), array_keys(CookieConsent::ASSETS))))
            ->name('asset');
    });

if ($cookieConsent->loggingEnabled()) {
    $middleware = (array) $cookieConsent->config('route.middleware', ['web']);
    $throttle = $cookieConsent->config('route.throttle', '30,1');

    if (is_string($throttle) && $throttle !== '') {
        $middleware[] = 'throttle:'.$throttle;
    }

    Route::prefix($prefix)
        ->name($name)
        ->middleware($middleware)
        ->group(static function (): void {
            Route::post('/', ConsentLogController::class)->name('store');
        });
}
