import type { CookieConsentConfig } from '../resources/js/core/types';

export function makeConfig(overrides: Partial<CookieConsentConfig> = {}): CookieConsentConfig {
    return {
        cookie: { name: 'cookie_consent', lifetime: 365, domain: null, path: '/', sameSite: 'Lax', secure: false },
        policyVersion: '1',
        categories: [
            { key: 'necessary', required: true, consentMode: ['security_storage'], cookies: [] },
            { key: 'functional', required: false, consentMode: ['functionality_storage', 'personalization_storage'], cookies: [] },
            { key: 'analytics', required: false, consentMode: ['analytics_storage'], cookies: [{ name: '_ga', pattern: '^_ga($|_)' }] },
            { key: 'marketing', required: false, consentMode: ['ad_storage', 'ad_user_data', 'ad_personalization'], cookies: [{ name: '_gcl_*', pattern: '^_gcl_' }] },
        ],
        consentMode: { enabled: true, event: 'cookie_consent_update' },
        log: { enabled: false, url: null, csrfToken: null },
        ui: { reloadOnWithdraw: false },
        ...overrides,
    };
}

/** Minimal version of the server-rendered banner markup. */
export const bannerMarkup = `
<div data-cc-root data-cc-layout="box">
  <section data-cc-banner hidden>
    <button data-cc-action="reject-all">Reject all</button>
    <button data-cc-action="accept-all">Accept all</button>
    <button data-cc-action="show-preferences">Preferences</button>
    <button data-cc-action="close">X</button>
  </section>
  <div data-cc-preferences hidden>
    <div role="dialog" tabindex="-1">
      <input type="checkbox" data-cc-category-toggle="necessary" checked disabled>
      <input type="checkbox" data-cc-category-toggle="functional">
      <input type="checkbox" data-cc-category-toggle="analytics">
      <input type="checkbox" data-cc-category-toggle="marketing">
      <button data-cc-action="save">Save</button>
    </div>
  </div>
</div>`;

export function clearCookies(): void {
    for (const part of document.cookie.split('; ')) {
        const name = part.split('=')[0];
        if (name) {
            document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/`;
        }
    }
}

export function storedState(): Record<string, unknown> | null {
    const match = document.cookie.split('; ').find((part) => part.startsWith('cookie_consent='));

    return match ? JSON.parse(decodeURIComponent(match.substring('cookie_consent='.length))) : null;
}

/** dataLayer entries are Arguments objects (gtag) or plain objects (events). */
export function gtagCalls(): unknown[][] {
    return (window.dataLayer ?? [])
        .filter((entry) => typeof entry === 'object' && entry !== null && !('event' in (entry as object)))
        .map((entry) => Array.from(entry as ArrayLike<unknown>));
}
