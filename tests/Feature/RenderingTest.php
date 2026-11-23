<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

function renderBlade(string $template): string
{
    return Blade::render($template);
}

it('prints Consent Mode defaults before the Google Tag Manager snippet', function (): void {
    config()->set('cookie-consent.google.gtm_id', 'GTM-TEST123');

    $html = renderBlade('@cookieConsentHead');

    $defaultsAt = strpos($html, "gtag('consent', 'default'");
    $gtmAt = strpos($html, 'googletagmanager.com/gtm.js');

    expect($defaultsAt)->toBeInt()
        ->and($gtmAt)->toBeInt()
        ->and($defaultsAt)->toBeLessThan($gtmAt)
        ->and($html)->toContain('ad_user_data')
        ->and($html)->toContain('ad_personalization')
        ->and($html)->toContain('wait_for_update')
        ->and($html)->toContain('GTM-TEST123')
        ->and($html)->toContain('cookie-consent.css');
});

it('delays Google tags in basic mode until analytics or marketing consent', function (): void {
    config()->set('cookie-consent.google.consent_mode.mode', 'basic');
    config()->set('cookie-consent.google.tag_ids', ['G-TEST', 'AW-TEST']);

    $html = renderBlade('@cookieConsentHead');

    expect($html)->toContain('type="text/plain" data-cc-category="analytics marketing" data-cc-require="any"')
        ->and($html)->toContain('gtag/js?id=G-TEST')
        ->and($html)->toContain("gtag('config', 'AW-TEST')");
});

it('loads Google tags immediately in advanced mode', function (): void {
    config()->set('cookie-consent.google.tag_ids', ['G-TEST']);

    expect(renderBlade('@cookieConsentHead'))->not->toContain('type="text/plain"');
});

it('adds the CSP nonce to inline scripts', function (): void {
    config()->set('cookie-consent.csp_nonce', 'abc123');

    expect(renderBlade('@cookieConsentHead'))->toContain('<script nonce="abc123">')
        ->and(renderBlade('@cookieConsentScripts'))->toContain('nonce="abc123"');
});

it('skips the stylesheet when styles are disabled', function (): void {
    config()->set('cookie-consent.ui.styles', false);

    expect(renderBlade('@cookieConsentHead'))->not->toContain('cookie-consent.css');
});

it('prints the client configuration and the script', function (): void {
    $html = renderBlade('@cookieConsentScripts');

    expect($html)->toContain('id="cookie-consent-config"')
        ->and($html)->toContain('"policyVersion":"1"')
        ->and($html)->toContain('cookie-consent.js?v=');
});

it('renders reject and accept with equal prominence and unchecked optional toggles', function (): void {
    $html = renderBlade('<x-cookie-consent::banner />');

    expect($html)->toContain('<button type="button" class="cc-btn cc-btn--primary" data-cc-action="reject-all">Reject all</button>')
        ->and($html)->toContain('<button type="button" class="cc-btn cc-btn--primary" data-cc-action="accept-all">Accept all</button>');

    // Necessary is checked and disabled; optional categories are never pre-ticked.
    preg_match_all('/<input[^>]*data-cc-category-toggle="([a-z]+)"[^>]*>/s', $html, $matches, PREG_SET_ORDER);
    $toggles = collect($matches)->mapWithKeys(fn (array $match): array => [$match[1] => $match[0]]);

    expect($toggles->keys()->all())->toBe(['necessary', 'functional', 'analytics', 'marketing'])
        ->and($toggles['necessary'])->toContain('checked disabled');

    foreach (['functional', 'analytics', 'marketing'] as $key) {
        expect($toggles[$key])->not->toContain('checked');
    }
});

it('renders Turkish texts', function (): void {
    app()->setLocale('tr');

    $html = renderBlade('<x-cookie-consent::banner />');

    expect($html)->toContain('Tümünü reddet')
        ->and($html)->toContain('Tümünü kabul et')
        ->and($html)->toContain('Zorunlu çerezler');
});

it('shows the data controller and cookie table with transfer abroad info', function (): void {
    config()->set('cookie-consent.data_controller.name', 'Örnek A.Ş.');

    $html = renderBlade('<x-cookie-consent::banner />');

    expect($html)->toContain('Örnek A.Ş.')
        ->and($html)->toContain('<code>_ga</code>')
        ->and($html)->toContain('<td>US</td>');
});

it('renders nothing when disabled', function (): void {
    config()->set('cookie-consent.enabled', false);

    expect(trim(renderBlade('@cookieConsentHead<x-cookie-consent::banner />@cookieConsentScripts')))->toBe('');
});

it('renders inert scripts with the script component', function (): void {
    $html = renderBlade('<x-cookie-consent::script category="analytics">console.log(1)</x-cookie-consent::script>');

    expect($html)->toContain('<script type="text/plain" data-cc-category="analytics"')
        ->and($html)->toContain('console.log(1)</script>');
});

it('supports the @consent Blade directive', function (): void {
    Route::get('/consent-test', fn () => Blade::render("@consent('analytics') yes @else no @endconsent"))->middleware('web');

    $this->get('/consent-test')->assertSee('no')->assertDontSee('yes');

    $this->withUnencryptedCookie('cookie_consent', $this->consentCookie(['analytics' => true]))
        ->get('/consent-test')
        ->assertSee('yes');
});
