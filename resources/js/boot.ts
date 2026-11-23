import { CookieConsentManager } from './core/manager';
import type { CookieConsentConfig } from './core/types';

declare global {
    interface Window {
        CookieConsent?: CookieConsentManager;
    }
}

export function readConfig(): CookieConsentConfig | null {
    const element = document.getElementById('cookie-consent-config');

    if (!element?.textContent) {
        return null;
    }

    try {
        return JSON.parse(element.textContent) as CookieConsentConfig;
    } catch {
        console.error('[cookie-consent] Invalid configuration JSON.');

        return null;
    }
}

/**
 * Creates the global `window.CookieConsent` instance (once) and initializes
 * it when the DOM is ready. Safe to call multiple times.
 */
export function boot(): CookieConsentManager | null {
    if (window.CookieConsent) {
        return window.CookieConsent;
    }

    const config = readConfig();

    if (!config) {
        return null;
    }

    const manager = new CookieConsentManager(config);
    window.CookieConsent = manager;

    const start = (): void => manager.init();

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
        start();
    }

    // SPA-style navigation: markup may have been swapped.
    for (const event of ['livewire:navigated', 'turbo:load', 'turbo:render', 'inertia:navigate', 'astro:page-load']) {
        document.addEventListener(event, () => manager.init());
    }

    return manager;
}
