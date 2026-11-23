/**
 * Optional Alpine.js integration: registers `$store.cookieConsent`.
 *
 * Vite:   import cookieConsent from '.../dist/cookie-consent-alpine.esm.js'; Alpine.plugin(cookieConsent);
 * Script: <script src=".../cookie-consent-alpine.js" defer> BEFORE Alpine's script tag.
 *
 * All logic lives in the core (window.CookieConsent); this is a thin reactive wrapper.
 */
import type { ConsentState } from '../core/types';
import type { CookieConsentManager } from '../core/manager';

interface AlpineLike {
    store(name: string, value?: unknown): unknown;
}

declare global {
    interface Window {
        Alpine?: AlpineLike;
        CookieConsent?: CookieConsentManager;
    }
}

export interface CookieConsentStore {
    state: ConsentState | null;
    readonly decided: boolean;
    has(category: string): boolean;
    acceptAll(): void;
    rejectAll(): void;
    save(choices: Record<string, boolean>): void;
    show(): void;
    showPreferences(): void;
    reset(): void;
}

const manager = (): CookieConsentManager | undefined => window.CookieConsent;

export default function cookieConsentPlugin(Alpine: AlpineLike): void {
    if (Alpine.store('cookieConsent')) {
        return;
    }

    const store: CookieConsentStore = {
        state: manager()?.getState() ?? null,
        get decided(): boolean {
            return this.state !== null;
        },
        has(category: string): boolean {
            // Touch `state` so Alpine re-evaluates when it changes.
            void this.state;

            return manager()?.hasConsent(category) ?? false;
        },
        acceptAll: () => manager()?.acceptAll(),
        rejectAll: () => manager()?.rejectAll(),
        save: (choices) => manager()?.save(choices),
        show: () => manager()?.show(),
        showPreferences: () => manager()?.showPreferences(),
        reset: () => manager()?.reset(),
    };

    Alpine.store('cookieConsent', store);

    const sync = (): void => {
        (Alpine.store('cookieConsent') as CookieConsentStore).state = manager()?.getState() ?? null;
    };

    document.addEventListener('cookie-consent:ready', sync);
    document.addEventListener('cookie-consent:updated', sync);
    document.addEventListener('cookie-consent:banner-shown', sync);
}

export function registerAlpineStore(): void {
    if (window.Alpine) {
        cookieConsentPlugin(window.Alpine);
    } else {
        document.addEventListener('alpine:init', () => {
            if (window.Alpine) {
                cookieConsentPlugin(window.Alpine);
            }
        });
    }
}
