<?php

declare(strict_types=1);

use Webmarka\CookieConsent\CookieConsent;

it('serves the bundled assets with long-term caching for the current hash', function (string $file, string $type): void {
    $version = app(CookieConsent::class)->assetVersion($file);

    $response = $this->get("/cookie-consent/assets/{$file}?v={$version}");

    $response->assertOk()
        ->assertHeader('Content-Type', $type)
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    expect($response->headers->get('Cache-Control'))->toContain('immutable');
})->with([
    ['cookie-consent.js', 'application/javascript; charset=UTF-8'],
    ['cookie-consent.css', 'text/css; charset=UTF-8'],
]);

it('uses short caching without a matching hash', function (): void {
    expect($this->get('/cookie-consent/assets/cookie-consent.js?v=old')->headers->get('Cache-Control'))
        ->not->toContain('immutable');
});

it('does not serve other files', function (string $file): void {
    $this->get('/cookie-consent/assets/'.$file)->assertNotFound();
})->with(['../composer.json', 'cookie-consent.esm.js', '..%2Fcomposer.json']);

it('uses published files with the public driver', function (): void {
    config()->set('cookie-consent.assets.driver', 'public');

    expect(app(CookieConsent::class)->assetUrl('cookie-consent.js'))
        ->toBe('http://localhost/vendor/cookie-consent/cookie-consent.js');
});

it('does not register the log route when logging is disabled', function (): void {
    $this->postJson('/cookie-consent', [])->assertNotFound();
});
