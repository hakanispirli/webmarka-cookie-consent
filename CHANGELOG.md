# Changelog

All notable changes to this project are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project follows [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- Google Consent Mode v2: `default` (all optional types denied) and synchronous `update` from the stored choice before Google tags; `ad_user_data` / `ad_personalization`; region defaults; `wait_for_update`, `ads_data_redaction`, `url_passthrough`; advanced and basic modes.
- Optional GTM / gtag.js snippets printed in the correct order.
- `dataLayer` event (`cookie_consent_update`) for GTM triggers.
- Dependency-free JavaScript core (~4 KB gzip) with `window.CookieConsent` API and DOM events.
- Blade components: banner + preference center, preferences button, cookie table, inert script.
- `@cookieConsentHead`, `@cookieConsentScripts` and `@consent` Blade directives.
- Script / iframe blocking with `type="text/plain"` / `data-cc-src` and `data-cc-category`.
- KVKK / GDPR friendly defaults: equal-prominence reject, no pre-ticked boxes, closing is not consent, easy withdrawal, cookie deletion on withdrawal, policy versioning.
- Optional consent records (database) with IP anonymization, CSV export and pruning commands.
- Standalone CSS themed with CSS custom properties (light / dark / auto), `box`, `bar` and `modal` layouts.
- Optional Alpine.js store; unstyled mode for Tailwind CSS.
- Turkish and English translations.
- AI-assisted installation prompts (English and Turkish) and an `llms.txt` API summary for AI agents.
- Community files: contributing guide, security policy, code of conduct, issue / PR templates, Dependabot.
