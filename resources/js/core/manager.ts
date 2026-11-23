import { pushConsentUpdate } from './consent-mode';
import { deleteCookie, deleteMatchingCookies, readCookie, writeCookie } from './cookies';
import { activateElements } from './scripts';
import type {
    ConsentAction,
    ConsentState,
    CookieConsentConfig,
    CookieConsentEventMap,
} from './types';
import { Ui } from './ui';

function uuid(): string {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }

    const bytes = new Uint8Array(16);
    crypto.getRandomValues(bytes);
    bytes[6] = (bytes[6]! & 0x0f) | 0x40;
    bytes[8] = (bytes[8]! & 0x3f) | 0x80;
    const hex = Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0')).join('');

    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
}

function readXsrfToken(): string | null {
    const value = readCookie('XSRF-TOKEN');

    return value ? decodeURIComponent(value) : null;
}

export class CookieConsentManager {
    readonly config: CookieConsentConfig;
    private state: ConsentState | null = null;
    private readonly ui: Ui;
    private initialized = false;

    constructor(config: CookieConsentConfig) {
        this.config = config;
        this.ui = new Ui({
            acceptAll: () => this.acceptAll(),
            rejectAll: () => this.rejectAll(),
            save: (choices) => this.save(choices),
            choices: () => this.choices(),
            emit: (name) => this.emit(`cookie-consent:${name}`, {}),
        });
        this.state = this.readState();
    }

    /** Reads the stored state, shows the banner if needed, activates allowed scripts. */
    init(): void {
        if (!this.initialized) {
            this.initialized = true;
            this.ui.bind();
            this.refresh();
            this.emit('cookie-consent:ready', { state: this.state });

            return;
        }

        this.refresh();
    }

    /** Re-evaluates the page, e.g. after Livewire / Turbo navigation. */
    refresh(): void {
        this.state = this.readState();

        if (this.state) {
            this.ui.hideBanner();
            this.activate();
        } else {
            this.ui.showBanner();
        }
    }

    getState(): ConsentState | null {
        return this.state ? { ...this.state, c: { ...this.state.c } } : null;
    }

    hasDecided(): boolean {
        return this.state !== null;
    }

    hasConsent(category: string): boolean {
        const definition = this.config.categories.find((item) => item.key === category);

        if (!definition) {
            return false;
        }

        return definition.required || this.state?.c[category] === true;
    }

    /** Current choices for every category (required ones always true). */
    choices(): Record<string, boolean> {
        const choices: Record<string, boolean> = {};

        for (const category of this.config.categories) {
            choices[category.key] = this.hasConsent(category.key);
        }

        return choices;
    }

    acceptAll(): void {
        const choices: Record<string, boolean> = {};

        for (const category of this.config.categories) {
            choices[category.key] = true;
        }

        this.update(choices, 'accept_all');
    }

    rejectAll(): void {
        this.update({}, 'reject_all');
    }

    save(choices: Record<string, boolean>): void {
        this.update(choices, 'custom');
    }

    /** Opens the first layer (banner). */
    show(): void {
        this.ui.showBanner();
    }

    showPreferences(): void {
        this.ui.showPreferences();
    }

    hide(): void {
        this.ui.hideAll();
    }

    /** Forgets the stored decision and asks again. */
    reset(): void {
        deleteCookie(this.config.cookie.name);

        if (this.config.cookie.domain) {
            document.cookie = `${this.config.cookie.name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=${this.config.cookie.path || '/'}; domain=${this.config.cookie.domain}`;
        }

        this.state = null;
        this.ui.showBanner();
    }

    /** Removes document listeners (e.g. before creating another instance). */
    destroy(): void {
        this.ui.hideAll();
        this.ui.unbind();
        this.initialized = false;
    }

    on<K extends keyof CookieConsentEventMap>(name: K, callback: (detail: CookieConsentEventMap[K]) => void): () => void {
        const listener = (event: Event): void => callback((event as CustomEvent<CookieConsentEventMap[K]>).detail);
        document.addEventListener(name, listener);

        return () => document.removeEventListener(name, listener);
    }

    private update(input: Record<string, boolean>, action: ConsentAction): void {
        const previous = this.state;
        const choices: Record<string, boolean> = {};

        for (const category of this.config.categories) {
            choices[category.key] = category.required || input[category.key] === true;
        }

        const state: ConsentState = {
            v: 1,
            id: previous?.id ?? uuid(),
            pv: this.config.policyVersion,
            c: choices,
            ts: Date.now(),
        };

        const withdrawn = previous
            ? Object.keys(previous.c).filter((key) => previous.c[key] === true && choices[key] === false)
            : [];

        this.state = state;
        writeCookie(this.config.cookie, JSON.stringify(state));
        this.deleteCookiesOfDeniedCategories(choices);
        pushConsentUpdate(this.config, choices, action);
        this.activate();
        this.ui.hideAll();
        this.log(state, action);
        this.emit('cookie-consent:updated', { state: this.getState()!, previous, action, withdrawn });

        // Scripts that already ran cannot be unloaded.
        if (withdrawn.length > 0 && this.config.ui.reloadOnWithdraw) {
            window.location.reload();
        }
    }

    private deleteCookiesOfDeniedCategories(choices: Record<string, boolean>): void {
        for (const category of this.config.categories) {
            if (!category.required && choices[category.key] !== true && category.cookies.length > 0) {
                deleteMatchingCookies(category.cookies);
            }
        }
    }

    private activate(): void {
        activateElements(document, (category) => this.hasConsent(category));
    }

    private readState(): ConsentState | null {
        const raw = readCookie(this.config.cookie.name);

        if (!raw) {
            return null;
        }

        try {
            const parsed: unknown = JSON.parse(decodeURIComponent(raw));

            if (
                typeof parsed === 'object' && parsed !== null
                && (parsed as ConsentState).v === 1
                && typeof (parsed as ConsentState).id === 'string'
                && (parsed as ConsentState).pv === this.config.policyVersion
                && typeof (parsed as ConsentState).c === 'object' && (parsed as ConsentState).c !== null
            ) {
                const state = parsed as ConsentState;
                const categories: Record<string, boolean> = {};

                for (const [key, value] of Object.entries(state.c)) {
                    if (typeof value === 'boolean') {
                        categories[key] = value;
                    }
                }

                return { v: 1, id: state.id, pv: state.pv, c: categories, ts: Number(state.ts) || 0 };
            }
        } catch {
            // Malformed cookie: ask again.
        }

        return null;
    }

    private log(state: ConsentState, action: ConsentAction): void {
        const { enabled, url } = this.config.log;

        if (!enabled || !url || typeof fetch !== 'function') {
            return;
        }

        const headers: Record<string, string> = {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };

        // Prefer the XSRF cookie: it stays fresh even when the HTML is served from a page cache.
        const xsrf = readXsrfToken();
        const csrf = this.config.log.csrfToken
            ?? document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content
            ?? null;

        if (xsrf) {
            headers['X-XSRF-TOKEN'] = xsrf;
        } else if (csrf) {
            headers['X-CSRF-TOKEN'] = csrf;
        }

        fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true,
            headers,
            body: JSON.stringify({
                id: state.id,
                action,
                policy_version: state.pv,
                categories: state.c,
                url: window.location.href.split('#')[0],
            }),
        }).catch(() => {
            // Logging must never break the page.
        });
    }

    private emit<K extends keyof CookieConsentEventMap>(name: K, detail: CookieConsentEventMap[K]): void {
        document.dispatchEvent(new CustomEvent(name, { detail }));
    }
}
