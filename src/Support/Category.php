<?php

declare(strict_types=1);

namespace Webmarka\CookieConsent\Support;

/**
 * A normalized cookie category from the configuration.
 */
final readonly class Category
{
    /**
     * @param  list<string>  $consentTypes  Google Consent Mode v2 consent types.
     * @param  list<CookieDefinition>  $cookies
     */
    public function __construct(
        public string $key,
        public bool $required,
        public array $consentTypes,
        public array $cookies,
        public string $title,
        public string $description,
    ) {}

    /**
     * @return array{key: string, required: bool, consentMode: list<string>, cookies: list<array{name: string, pattern: string|null}>}
     */
    public function toClientArray(): array
    {
        return [
            'key' => $this->key,
            'required' => $this->required,
            'consentMode' => $this->consentTypes,
            'cookies' => array_map(
                static fn (CookieDefinition $cookie): array => [
                    'name' => $cookie->name,
                    'pattern' => $cookie->pattern,
                ],
                $this->cookies,
            ),
        ];
    }
}
