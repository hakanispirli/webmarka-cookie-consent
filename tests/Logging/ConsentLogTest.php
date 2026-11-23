<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Webmarka\CookieConsent\Enums\ConsentAction;
use Webmarka\CookieConsent\Models\ConsentLog;

function validPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'id' => '6f1c1b7e-8a43-4c1e-9d6a-0d4c3c1b2a10',
        'action' => 'custom',
        'policy_version' => '1',
        'categories' => ['necessary' => true, 'functional' => false, 'analytics' => true, 'marketing' => false],
        'url' => 'https://example.com/page',
    ], $overrides);
}

it('stores a consent record with an anonymized IP', function (): void {
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.42'])
        ->postJson('/cookie-consent', validPayload())
        ->assertNoContent();

    $log = ConsentLog::query()->sole();

    expect($log->consent_id)->toBe('6f1c1b7e-8a43-4c1e-9d6a-0d4c3c1b2a10')
        ->and($log->action)->toBe(ConsentAction::Custom)
        ->and($log->categories)->toBe(['necessary' => true, 'functional' => false, 'analytics' => true, 'marketing' => false])
        ->and($log->consent_mode['analytics_storage'])->toBe('granted')
        ->and($log->consent_mode['ad_user_data'])->toBe('denied')
        ->and($log->ip_address)->toBe('203.0.113.0')
        ->and($log->policy_version)->toBe('1');
});

it('hashes or drops the IP when configured', function (string $mode, ?string $expected): void {
    config()->set('cookie-consent.logging.ip', $mode);

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.42'])
        ->postJson('/cookie-consent', validPayload())
        ->assertNoContent();

    $ip = ConsentLog::query()->sole()->ip_address;

    $mode === 'hash'
        ? expect($ip)->toHaveLength(64)->not->toContain('203.0.113')
        : expect($ip)->toBe($expected);
})->with([
    ['hash', null],
    ['none', null],
    ['full', '203.0.113.42'],
]);

it('always records required categories as granted', function (): void {
    $this->postJson('/cookie-consent', validPayload(['categories' => ['necessary' => false]]))->assertNoContent();

    expect(ConsentLog::query()->sole()->categories['necessary'])->toBeTrue();
});

it('rejects invalid payloads', function (array $payload): void {
    $this->postJson('/cookie-consent', $payload)->assertUnprocessable();

    expect(ConsentLog::query()->count())->toBe(0);
})->with([
    'unknown category' => [validPayload(['categories' => ['tracking' => true]])],
    'bad id' => [validPayload(['id' => 'abc'])],
    'bad action' => [validPayload(['action' => 'maybe'])],
    'old policy' => [validPayload(['policy_version' => '0'])],
    'non boolean' => [validPayload(['categories' => ['analytics' => 'yes']])],
]);

it('prunes old records', function (): void {
    $this->postJson('/cookie-consent', validPayload())->assertNoContent();
    ConsentLog::query()->update(['created_at' => Carbon::now()->subDays(2000)]);
    $this->postJson('/cookie-consent', validPayload(['action' => 'accept_all']))->assertNoContent();

    $this->artisan('cookie-consent:prune')->assertSuccessful();

    expect(ConsentLog::query()->count())->toBe(1);
});

it('exports records as CSV and neutralizes formulas', function (): void {
    $this->postJson('/cookie-consent', validPayload(['url' => '=HYPERLINK("http://evil")']))->assertNoContent();

    $path = sys_get_temp_dir().'/cookie-consent-export-'.uniqid().'.csv';

    $this->artisan('cookie-consent:export', ['path' => $path])->assertSuccessful();

    $csv = (string) file_get_contents($path);
    unlink($path);

    expect($csv)->toContain('consent_id')
        ->and($csv)->toContain('6f1c1b7e-8a43-4c1e-9d6a-0d4c3c1b2a10')
        ->and($csv)->toContain("'=HYPERLINK");
});
