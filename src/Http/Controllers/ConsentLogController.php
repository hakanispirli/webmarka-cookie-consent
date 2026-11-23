<?php

declare(strict_types=1);

namespace Webmarka\CookieConsent\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\IpUtils;
use Webmarka\CookieConsent\CookieConsent;
use Webmarka\CookieConsent\Http\Requests\StoreConsentRequest;
use Webmarka\CookieConsent\Models\ConsentLog;

/**
 * Stores a consent decision as proof of consent.
 */
final class ConsentLogController
{
    public function __invoke(StoreConsentRequest $request, CookieConsent $cookieConsent): Response
    {
        /** @var array<string, bool|int|string> $submitted */
        $submitted = $request->validated('categories');
        $categories = [];

        foreach ($cookieConsent->categories() as $key => $category) {
            $categories[$key] = $category->required || filter_var($submitted[$key] ?? false, FILTER_VALIDATE_BOOL);
        }

        $url = $request->validated('url');
        $userId = $request->user()?->getAuthIdentifier();

        ConsentLog::query()->create([
            'consent_id' => $request->validated('id'),
            'action' => $request->validated('action'),
            'categories' => $categories,
            'consent_mode' => $cookieConsent->consentModeFor($categories),
            'policy_version' => $cookieConsent->policyVersion(),
            'locale' => app()->getLocale(),
            'url' => is_string($url) ? Str::limit($url, 2000, '') : null,
            'ip_address' => $this->ip($request->ip(), $cookieConsent),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            'user_id' => is_scalar($userId) ? (string) $userId : null,
        ]);

        return response()->noContent();
    }

    private function ip(?string $ip, CookieConsent $cookieConsent): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        return match ($cookieConsent->config('logging.ip', 'anonymize')) {
            'full' => $ip,
            'hash' => hash_hmac('sha256', $ip, (string) config('app.key')),
            'none' => null,
            default => IpUtils::anonymize($ip),
        };
    }
}
