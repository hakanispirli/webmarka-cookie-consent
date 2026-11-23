<?php

declare(strict_types=1);

namespace Webmarka\CookieConsent\Support;

use Illuminate\Support\Str;

/**
 * The visitor's stored decision, parsed from the consent cookie.
 *
 * Cookie format (URL-encoded JSON, written by the JavaScript core):
 *   {"v":1,"id":"<uuid>","pv":"<policy version>","c":{"analytics":true,...},"ts":<ms>}
 */
final readonly class ConsentState
{
    public const int SCHEMA_VERSION = 1;

    /**
     * @param  array<string, bool>  $categories
     */
    public function __construct(
        public string $id,
        public string $policyVersion,
        public array $categories,
        public ?int $timestamp,
    ) {}

    /**
     * Returns null when the value is missing, malformed or belongs to a
     * different policy version (the visitor must then be asked again).
     */
    public static function fromCookie(?string $value, string $policyVersion): ?self
    {
        if ($value === null || $value === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        if (! is_array($decoded)) {
            $decoded = json_decode(rawurldecode($value), true);
        }

        if (! is_array($decoded)
            || ($decoded['v'] ?? null) !== self::SCHEMA_VERSION
            || ! is_string($decoded['id'] ?? null)
            || ! Str::isUuid($decoded['id'])
            || ($decoded['pv'] ?? null) !== $policyVersion
            || ! is_array($decoded['c'] ?? null)
        ) {
            return null;
        }

        $categories = [];

        foreach ($decoded['c'] as $key => $granted) {
            if (is_string($key) && is_bool($granted)) {
                $categories[$key] = $granted;
            }
        }

        return new self(
            id: $decoded['id'],
            policyVersion: $policyVersion,
            categories: $categories,
            timestamp: is_int($decoded['ts'] ?? null) ? $decoded['ts'] : null,
        );
    }

    public function granted(string $category): bool
    {
        return ($this->categories[$category] ?? false) === true;
    }
}
