<?php

declare(strict_types=1);

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Http\Request;
use Webmarka\CookieConsent\CookieConsent;
use Webmarka\CookieConsent\Support\ConsentState;

function cookieConsent(): CookieConsent
{
    return app(CookieConsent::class);
}

function requestWithConsent(string $value, string $name = 'cookie_consent'): Request
{
    return Request::create('/', 'GET', cookies: [$name => $value]);
}

it('denies every optional consent type by default', function (): void {
    expect(cookieConsent()->consentModeDefaults())->toBe([
        'ad_storage' => 'denied',
        'ad_user_data' => 'denied',
        'ad_personalization' => 'denied',
        'analytics_storage' => 'denied',
        'functionality_storage' => 'denied',
        'personalization_storage' => 'denied',
        'security_storage' => 'granted',
    ]);
});

it('maps marketing to all three Consent Mode v2 ad types', function (): void {
    $states = cookieConsent()->consentModeFor(['marketing' => true]);

    expect($states['ad_storage'])->toBe('granted')
        ->and($states['ad_user_data'])->toBe('granted')
        ->and($states['ad_personalization'])->toBe('granted')
        ->and($states['analytics_storage'])->toBe('denied');
});

it('reads the stored decision from the consent cookie', function (): void {
    $request = requestWithConsent($this->consentCookie(['analytics' => true, 'marketing' => false]));

    expect(cookieConsent()->hasDecided($request))->toBeTrue()
        ->and(cookieConsent()->granted('analytics', $request))->toBeTrue()
        ->and(cookieConsent()->granted('marketing', $request))->toBeFalse()
        ->and(cookieConsent()->granted('necessary', $request))->toBeTrue()
        ->and(cookieConsent()->granted('unknown', $request))->toBeFalse();
});

it('accepts URL-encoded cookie values', function (): void {
    $request = requestWithConsent(rawurlencode($this->consentCookie(['analytics' => true])));

    expect(cookieConsent()->granted('analytics', $request))->toBeTrue();
});

it('ignores consent given for another policy version', function (): void {
    config()->set('cookie-consent.policy_version', '2');

    $request = requestWithConsent($this->consentCookie(['analytics' => true], '1'));

    expect(cookieConsent()->hasDecided($request))->toBeFalse()
        ->and(cookieConsent()->granted('analytics', $request))->toBeFalse();
});

it('rejects malformed cookies', function (string $value): void {
    expect(ConsentState::fromCookie($value, '1'))->toBeNull();
})->with([
    'not json' => ['nope'],
    'wrong schema' => ['{"v":2,"id":"6f1c1b7e-8a43-4c1e-9d6a-0d4c3c1b2a10","pv":"1","c":{}}'],
    'bad id' => ['{"v":1,"id":"x","pv":"1","c":{}}'],
    'no categories' => ['{"v":1,"id":"6f1c1b7e-8a43-4c1e-9d6a-0d4c3c1b2a10","pv":"1"}'],
]);

it('ignores non-boolean category values', function (): void {
    $state = ConsentState::fromCookie('{"v":1,"id":"6f1c1b7e-8a43-4c1e-9d6a-0d4c3c1b2a10","pv":"1","c":{"analytics":"yes","marketing":true}}', '1');

    expect($state?->categories)->toBe(['marketing' => true]);
});

it('excludes the consent cookie from encryption', function (): void {
    expect(app(EncryptCookies::class)->isDisabled('cookie_consent'))->toBeTrue();
});

it('validates category keys and consent types', function (array $categories): void {
    config()->set('cookie-consent.categories', $categories);

    cookieConsent()->categories();
})->with([
    'bad key' => [['Bad Key' => ['consent_mode' => []]]],
    'unknown type' => [['ads' => ['consent_mode' => ['ad_storagee']]]],
])->throws(InvalidArgumentException::class);

it('applies region specific defaults', function (): void {
    config()->set('cookie-consent.google.consent_mode.region_defaults', [
        ['region' => ['US-CA'], 'analytics_storage' => 'granted', 'bogus' => 'granted'],
    ]);

    $regions = cookieConsent()->regionDefaults();

    expect($regions)->toHaveCount(1)
        ->and($regions[0]['region'])->toBe(['US-CA'])
        ->and($regions[0]['analytics_storage'])->toBe('granted')
        ->and($regions[0]['ad_storage'])->toBe('denied')
        ->and($regions[0])->not->toHaveKey('bogus');
});

it('resolves policy links', function (): void {
    config()->set('cookie-consent.urls.privacy_policy', '/aydinlatma-metni');
    config()->set('cookie-consent.urls.cookie_policy', 'https://example.com/cookies');

    expect(cookieConsent()->url('privacy_policy'))->toBe('http://localhost/aydinlatma-metni')
        ->and(cookieConsent()->url('cookie_policy'))->toBe('https://example.com/cookies');
});
