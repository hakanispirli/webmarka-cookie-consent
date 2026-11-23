<?php

declare(strict_types=1);

namespace Webmarka\CookieConsent\Support;

/**
 * A cookie listed in the configuration, used for the cookie table
 * (privacy notice) and for deleting cookies when consent is withdrawn.
 */
final readonly class CookieDefinition
{
    public function __construct(
        public string $name,
        public ?string $pattern,
        public string $provider,
        public string $purpose,
        public string $duration,
        public ?string $transfersAbroad,
    ) {}

    /**
     * @param  array<string, mixed>  $definition
     */
    public static function fromArray(array $definition): self
    {
        $string = static fn (string $key, string $default = ''): string => is_scalar($definition[$key] ?? null)
            ? (string) $definition[$key]
            : $default;

        $pattern = $string('pattern');
        $abroad = $string('transfers_abroad');

        return new self(
            name: $string('name'),
            pattern: $pattern !== '' ? $pattern : null,
            provider: $string('provider'),
            purpose: self::translate($string('purpose')),
            duration: self::translate($string('duration')),
            transfersAbroad: $abroad !== '' ? $abroad : null,
        );
    }

    private static function translate(string $value): string
    {
        if ($value === '' || ! str_contains($value, '::') && ! str_contains($value, '.')) {
            return $value;
        }

        $translated = __($value);

        return is_string($translated) ? $translated : $value;
    }
}
