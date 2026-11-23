# AI install prompt (English)

Copy everything inside the box below and paste it into your AI coding assistant (Claude Code, Cursor, GitHub Copilot, ChatGPT, Codex, Windsurf, ...) **while your Laravel project is open**. The assistant inspects your project first and asks you before it makes changes that need your decision.

````text
You are working in my Laravel project. Integrate the Composer package `webmarka/cookie-consent`
(https://github.com/hakanispirli/webmarka-cookie-consent) to add a KVKK / GDPR compliant cookie
consent banner with Google Consent Mode v2.

Documentation: after installing, read `vendor/webmarka/cookie-consent/README.md` and
`vendor/webmarka/cookie-consent/llms.txt`. Treat them as the source of truth. Do not invent
directives, config keys or APIs that are not documented there.

## Step 1: Inspect (read only, change nothing yet)
Report back briefly:
1. Laravel and PHP versions. The package needs Laravel 13+ and PHP 8.3+. If they are not met, stop and tell me.
2. The Blade layout(s) that render `<head>` and `</body>` (e.g. `resources/views/layouts/*.blade.php`,
   `resources/views/components/layouts/*`, `resources/views/app.blade.php` for Inertia).
3. Every tracking / third-party snippet: Google Tag Manager, gtag.js / GA4, Google Ads, Meta Pixel,
   TikTok, LinkedIn Insight, Hotjar, Microsoft Clarity, Yandex Metrica, chat widgets, YouTube / Google Maps
   embeds. Search Blade views, JS files, config and `.env`.
4. Any existing cookie banner (package or custom code) or existing `gtag('consent', ...)` calls.
5. Frontend stack: Alpine.js, Tailwind CSS, Livewire, Inertia (Vue/React), Vite.
6. A Content Security Policy (nonce) or full-page caching (Cloudflare, spatie/laravel-responsecache, ...).
7. The locales the site uses.

## Step 2: Install
Run:
    composer require webmarka/cookie-consent
    php artisan cookie-consent:install

## Step 3: Layout
In every main layout:
- Add `@cookieConsentHead` as the first element in `<head>`, right after the charset and viewport meta
  tags and BEFORE any Google Tag Manager / gtag.js code.
- Add `<x-cookie-consent::banner />` and `@cookieConsentScripts` right before `</body>`.
- Add `<x-cookie-consent::preferences-button />` to the footer so visitors can change or withdraw consent
  on every page.
- Add `<meta name="csrf-token" content="{{ csrf_token() }}">` if it is missing.
- For Alpine.js loaded from a CDN, place `@cookieConsentScripts` in `<head>` before Alpine's script and set
  `'assets' => ['alpine' => true]`. For Alpine through Vite, register the plugin as shown in the README.

## Step 4: Google tags
- Preferred: move the GTM container ID / gtag IDs to `.env` (`COOKIE_CONSENT_GTM_ID`,
  `COOKIE_CONSENT_GOOGLE_TAG_IDS=G-...,AW-...`) and remove the hard-coded snippets. The package prints them
  in the correct order.
- Otherwise, keep the existing snippet but make sure it comes AFTER `@cookieConsentHead`.
- Remove GTM's `<noscript><iframe ...>` fallback. It loads without consent.
- Remove any existing `gtag('consent', 'default' | 'update', ...)` code. The package handles Consent Mode.

## Step 5: Block other third-party code until consent
- `<script>` → `<script type="text/plain" data-cc-category="CATEGORY">` (keep `src` and other attributes).
- `<iframe src="...">` → `<iframe data-cc-src="..." data-cc-category="CATEGORY">`.
- Categories: `analytics` (Hotjar, Clarity, Yandex, ...), `marketing` (Meta Pixel, TikTok, LinkedIn,
  YouTube embeds, ...), `functional` (chat widgets, maps, ...).
- Never block code the site needs to work (app bundle, payments, CSRF, captcha required for forms).
- In Blade you may use `<x-cookie-consent::script category="...">...</x-cookie-consent::script>`.

## Step 6: Configuration
Fill in `.env`. ASK ME for these values. Never invent company details or URLs:
    COOKIE_CONSENT_CONTROLLER_NAME=
    COOKIE_CONSENT_CONTROLLER_EMAIL=
    COOKIE_CONSENT_PRIVACY_URL=
    COOKIE_CONSENT_COOKIE_POLICY_URL=
In `config/cookie-consent.php`, add every cookie set by the services found in Step 1 to the right
category (`name` or regex `pattern`, `provider`, `purpose`, `duration`, `transfers_abroad` country code
or null). Keep `necessary` in sync with the session cookie name.

## Step 7: Optional (ask me first)
- Consent records: `php artisan cookie-consent:install --migrations`, `php artisan migrate`,
  `COOKIE_CONSENT_LOGGING=true`, and schedule `cookie-consent:prune` daily in `routes/console.php`.
- A cookie policy page that renders `<x-cookie-consent::cookie-table />`.
- Tailwind styling (`'ui' => ['styles' => false]` + `php artisan vendor:publish --tag=cookie-consent-views`)
  or Google Consent Mode `basic` instead of `advanced`.

## Step 8: Verify
- Run `php artisan optimize:clear` and the project's test suite.
- Render a page and confirm in the HTML that `gtag('consent', 'default'` appears before any
  `googletagmanager.com` script.
- Give me a manual browser checklist: the banner appears in a private window; no `_ga` / `_gcl_*` / `_fbp`
  cookies before a choice; "Accept all" pushes `consent update` with `granted` to `dataLayer`; "Reject all"
  keeps `denied`; the footer button reopens the preferences; Google Tag Assistant shows the consent states.

## Rules
- Make small, focused changes. Don't refactor unrelated code.
- At the end, list every changed file and everything I still need to do manually.
- Remind me that this is a technical integration, not legal advice, and that the texts, cookie list and
  cross-border transfer basis should be reviewed by a lawyer.
````

> [!TIP]
> Shorter version for simple projects:
>
> ```text
> Install `webmarka/cookie-consent` in this Laravel project following vendor/webmarka/cookie-consent/README.md:
> run the install command, add @cookieConsentHead first in <head> (before GTM/gtag), the banner component and
> @cookieConsentScripts before </body>, the preferences button in the footer, move GTM/gtag IDs to .env, convert
> other tracking scripts to type="text/plain" data-cc-category="...", and ask me for the data controller name,
> e-mail and policy URLs. List changed files at the end.
> ```
