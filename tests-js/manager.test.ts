import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { CookieConsentManager } from '../resources/js/core/manager';
import { bannerMarkup, clearCookies, gtagCalls, makeConfig, storedState } from './helpers';

function click(action: string, scope: ParentNode = document): void {
    scope.querySelector<HTMLElement>(`[data-cc-action="${action}"]`)!.click();
}

describe('CookieConsentManager', () => {
    let manager: CookieConsentManager;

    beforeEach(() => {
        clearCookies();
        window.dataLayer = [];
        document.body.innerHTML = bannerMarkup;
    });

    afterEach(() => {
        manager?.destroy();
    });

    it('shows the banner when there is no decision', () => {
        manager = new CookieConsentManager(makeConfig());
        manager.init();

        expect(document.querySelector<HTMLElement>('[data-cc-banner]')!.hidden).toBe(false);
        expect(manager.hasDecided()).toBe(false);
        expect(manager.hasConsent('necessary')).toBe(true);
        expect(manager.hasConsent('analytics')).toBe(false);
    });

    it('optional toggles start unchecked (no pre-ticked boxes)', () => {
        manager = new CookieConsentManager(makeConfig());
        manager.init();
        click('show-preferences');

        const toggles = Array.from(document.querySelectorAll<HTMLInputElement>('[data-cc-category-toggle]:not([disabled])'));
        expect(toggles.length).toBe(3);
        expect(toggles.every((toggle) => !toggle.checked)).toBe(true);
    });

    it('accept all stores consent, updates Consent Mode and hides the banner', () => {
        const listener = vi.fn();
        document.addEventListener('cookie-consent:updated', listener);

        manager = new CookieConsentManager(makeConfig());
        manager.init();
        click('accept-all');

        const state = storedState();
        expect(state).toMatchObject({ v: 1, pv: '1', c: { necessary: true, functional: true, analytics: true, marketing: true } });
        expect(String(state!.id)).toMatch(/^[0-9a-f-]{36}$/);

        expect(gtagCalls()).toContainEqual(['consent', 'update', expect.objectContaining({
            ad_storage: 'granted', ad_user_data: 'granted', ad_personalization: 'granted', analytics_storage: 'granted',
        })]);
        expect(window.dataLayer).toContainEqual(expect.objectContaining({ event: 'cookie_consent_update', cookie_consent_action: 'accept_all' }));
        expect(document.querySelector<HTMLElement>('[data-cc-banner]')!.hidden).toBe(true);
        expect(listener).toHaveBeenCalledOnce();

        document.removeEventListener('cookie-consent:updated', listener);
    });

    it('reject all and the close button deny every optional category', () => {
        for (const action of ['reject-all', 'close']) {
            manager?.destroy();
            clearCookies();
            window.dataLayer = [];
            document.body.innerHTML = bannerMarkup;
            manager = new CookieConsentManager(makeConfig());
            manager.init();
            click(action);

            expect(storedState()).toMatchObject({ c: { necessary: true, functional: false, analytics: false, marketing: false } });
            expect(gtagCalls()).toContainEqual(['consent', 'update', expect.objectContaining({ ad_storage: 'denied', analytics_storage: 'denied', security_storage: 'granted' })]);
        }
    });

    it('saves granular choices from the preference center', () => {
        manager = new CookieConsentManager(makeConfig());
        manager.init();
        click('show-preferences');
        document.querySelector<HTMLInputElement>('[data-cc-category-toggle="analytics"]')!.checked = true;
        click('save');

        expect(storedState()).toMatchObject({ c: { analytics: true, marketing: false, functional: false } });
        expect(manager.hasConsent('analytics')).toBe(true);
        expect(document.querySelector<HTMLElement>('[data-cc-preferences]')!.hidden).toBe(true);
    });

    it('restores a stored decision without showing the banner and activates scripts', () => {
        document.documentElement.removeAttribute('data-ran');
        document.body.insertAdjacentHTML('beforeend', `<script type="text/plain" data-cc-category="analytics">document.documentElement.setAttribute('data-ran', 'ga')</script>`);
        document.cookie = `cookie_consent=${encodeURIComponent(JSON.stringify({ v: 1, id: '6f1c1b7e-8a43-4c1e-9d6a-0d4c3c1b2a10', pv: '1', c: { analytics: true }, ts: 1 }))}; path=/`;

        manager = new CookieConsentManager(makeConfig());
        manager.init();

        expect(document.querySelector<HTMLElement>('[data-cc-banner]')!.hidden).toBe(true);
        expect(document.documentElement.getAttribute('data-ran')).toBe('ga');
    });

    it('asks again when the policy version changes', () => {
        document.cookie = `cookie_consent=${encodeURIComponent(JSON.stringify({ v: 1, id: '6f1c1b7e-8a43-4c1e-9d6a-0d4c3c1b2a10', pv: '1', c: { analytics: true }, ts: 1 }))}; path=/`;

        manager = new CookieConsentManager(makeConfig({ policyVersion: '2' }));
        manager.init();

        expect(manager.hasDecided()).toBe(false);
        expect(document.querySelector<HTMLElement>('[data-cc-banner]')!.hidden).toBe(false);
    });

    it('deletes cookies of withdrawn categories and keeps the consent id', () => {
        manager = new CookieConsentManager(makeConfig());
        manager.init();
        click('accept-all');
        const id = storedState()!.id;

        document.cookie = '_ga=GA1.1.123; path=/';
        document.cookie = '_ga_ABC=GS1; path=/';
        document.cookie = '_gcl_au=1.1; path=/';
        document.cookie = 'unrelated=1; path=/';

        const listener = vi.fn();
        document.addEventListener('cookie-consent:updated', listener);
        manager.rejectAll();

        expect(document.cookie).not.toMatch(/_ga=/);
        expect(document.cookie).not.toMatch(/_ga_ABC=/);
        expect(document.cookie).not.toMatch(/_gcl_au=/);
        expect(document.cookie).toMatch(/unrelated=1/);
        expect(storedState()!.id).toBe(id);
        expect(listener.mock.calls[0]![0].detail.withdrawn).toEqual(['functional', 'analytics', 'marketing']);

        document.removeEventListener('cookie-consent:updated', listener);
    });

    it('closes the preference center with Escape', () => {
        manager = new CookieConsentManager(makeConfig());
        manager.init();
        click('show-preferences');
        expect(document.querySelector<HTMLElement>('[data-cc-preferences]')!.hidden).toBe(false);

        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
        expect(document.querySelector<HTMLElement>('[data-cc-preferences]')!.hidden).toBe(true);
        // Closing the preference center is not a decision.
        expect(manager.hasDecided()).toBe(false);
    });

    it('posts a consent record when logging is enabled', () => {
        const fetchMock = vi.fn().mockResolvedValue(new Response(null, { status: 204 }));
        vi.stubGlobal('fetch', fetchMock);

        manager = new CookieConsentManager(makeConfig({ log: { enabled: true, url: '/cookie-consent', csrfToken: 'token' } }));
        manager.init();
        click('reject-all');

        expect(fetchMock).toHaveBeenCalledOnce();
        const [url, options] = fetchMock.mock.calls[0]!;
        expect(url).toBe('/cookie-consent');
        expect(options.headers['X-CSRF-TOKEN']).toBe('token');
        expect(JSON.parse(options.body)).toMatchObject({ action: 'reject_all', policy_version: '1', categories: { analytics: false } });

        vi.unstubAllGlobals();
    });
});
