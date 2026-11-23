# Webmarka Cookie Consent

[![Tests](https://img.shields.io/github/actions/workflow/status/hakanispirli/webmarka-cookie-consent/tests.yml?branch=main&label=tests)](https://github.com/hakanispirli/webmarka-cookie-consent/actions/workflows/tests.yml)
[![Packagist](https://img.shields.io/packagist/v/webmarka/cookie-consent.svg)](https://packagist.org/packages/webmarka/cookie-consent)
[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

[Türkçe](README.tr.md)

Cookie consent banner for Laravel 13+ with **Google Consent Mode v2** and **KVKK / GDPR** compliant defaults.

<img src="https://raw.githubusercontent.com/hakanispirli/webmarka-cookie-consent/main/docs/images/banner-light.png" alt="Cookie banner" width="760">

## What it does

- Shows a cookie banner and a preference center (necessary, functional, analytics, marketing).
- Sends consent signals to Google (`ad_storage`, `analytics_storage`, `ad_user_data`, `ad_personalization`).
- Blocks analytics and marketing scripts until the visitor consents.
- Optionally stores each decision in your database as proof of consent.

## Why use it

- **Keep Google Ads features.** Google requires Consent Mode v2 for EEA/UK traffic. Without it, remarketing and conversion measurement are limited.
- **KVKK / GDPR compliant defaults.** "Reject all" is as visible as "Accept all", no box is pre-ticked, closing the banner is not consent, and consent can be withdrawn at any time.
- **Easy to install.** No Node.js or build step on your server. It works with any frontend: Blade, Livewire, Inertia, Alpine.js, Tailwind CSS or Bootstrap.
- **Lightweight and self-hosted.** About 4 KB of JavaScript, no external services.

## How it works

1. `@cookieConsentHead` runs first in `<head>` and sets every Google consent type to `denied`. For a returning visitor, it immediately restores their saved choice.
2. The banner asks first-time visitors. Their choice is saved in a first-party cookie.
3. After a choice, Google receives `consent update`, and blocked scripts (`type="text/plain"`) for the allowed categories run.

## Installation

```bash
composer require webmarka/cookie-consent
php artisan cookie-consent:install
```

Add it to your layout:

```blade
<head>
    @cookieConsentHead   {{-- first, before Google Tag Manager / gtag.js --}}
</head>
<body>
    ...
    <x-cookie-consent::preferences-button />   {{-- e.g. in the footer --}}
    <x-cookie-consent::banner />
    @cookieConsentScripts
</body>
```

Set your details in `.env`:

```dotenv
COOKIE_CONSENT_CONTROLLER_NAME="Example Ltd."
COOKIE_CONSENT_PRIVACY_URL=/privacy-notice
COOKIE_CONSENT_COOKIE_POLICY_URL=/cookie-policy

# optional: the package prints Google tags in the correct order
COOKIE_CONSENT_GTM_ID=GTM-XXXXXXX
COOKIE_CONSENT_GOOGLE_TAG_IDS=G-XXXXXXXXXX,AW-XXXXXXXXX
```

## Blocking other scripts

```html
<script type="text/plain" data-cc-category="analytics" src="https://example.com/analytics.js"></script>
<script type="text/plain" data-cc-category="marketing">/* Meta Pixel, TikTok, ... */</script>
<iframe data-cc-src="https://www.youtube-nocookie.com/embed/xyz" data-cc-category="marketing"></iframe>
```

In Blade: `@consent('marketing') ... @endconsent`. In JavaScript: `CookieConsent.hasConsent('analytics')`.

## Configuration

Categories, cookie list, layout (`box`, `bar`, `modal`), theme, Consent Mode (`advanced` / `basic`) and consent records are set in `config/cookie-consent.php`. List the cookies your site uses there. They are shown to visitors and deleted when consent is withdrawn.

Consent records (optional):

```bash
php artisan cookie-consent:install --migrations && php artisan migrate
# .env: COOKIE_CONSENT_LOGGING=true
php artisan cookie-consent:export consents.csv
```

To change texts or markup, publish them with `--tag=cookie-consent-lang` or `--tag=cookie-consent-views`. With Tailwind, set `ui.styles` to `false` and style the published views.

## Install with AI

Paste the ready-made prompt into Claude Code, Cursor, Copilot or ChatGPT, and the assistant integrates the package into your project: [English prompt](docs/ai/install-prompt.en.md) · [Turkish prompt](docs/ai/install-prompt.tr.md). There is also an API summary for AI tools: [`llms.txt`](llms.txt).

## Notes

- Only advertisers using Google Ads need Consent Mode v2. Publishers using AdSense, Ad Manager or AdMob need a Google-certified CMP (IAB TCF) instead.
- This package is a technical tool, not legal advice. Have your cookie list and texts reviewed by a lawyer.

## License

[MIT](LICENSE). Contributions are welcome, see [CONTRIBUTING.md](CONTRIBUTING.md). Report security issues as described in [SECURITY.md](SECURITY.md).

---

Developed by [webmarka.com](https://webmarka.com/?ref=github-cookie-consent)
