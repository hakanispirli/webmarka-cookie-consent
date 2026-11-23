const FOCUSABLE = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    'summary',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

export interface UiHandlers {
    acceptAll(): void;
    rejectAll(): void;
    save(choices: Record<string, boolean>): void;
    /** Current choices used to pre-fill the toggles. */
    choices(): Record<string, boolean>;
    emit(name: 'banner-shown' | 'banner-hidden' | 'preferences-shown' | 'preferences-hidden'): void;
}

/**
 * Controls the server-rendered banner markup. Uses event delegation on the
 * document, so it keeps working when the markup is replaced (Livewire
 * navigate, Turbo, Inertia...).
 */
export class Ui {
    private lastFocused: HTMLElement | null = null;
    private bound = false;

    constructor(private readonly handlers: UiHandlers) {}

    root(): HTMLElement | null {
        return document.querySelector<HTMLElement>('[data-cc-root]');
    }

    private banner(): HTMLElement | null {
        return this.root()?.querySelector<HTMLElement>('[data-cc-banner]') ?? null;
    }

    private preferences(): HTMLElement | null {
        return this.root()?.querySelector<HTMLElement>('[data-cc-preferences]') ?? null;
    }

    bind(): void {
        if (this.bound) {
            return;
        }

        this.bound = true;
        document.addEventListener('click', this.onClick);
        document.addEventListener('keydown', this.onKeydown);
    }

    unbind(): void {
        this.bound = false;
        document.removeEventListener('click', this.onClick);
        document.removeEventListener('keydown', this.onKeydown);
    }

    isBannerVisible(): boolean {
        const banner = this.banner();

        return !!banner && !banner.hidden;
    }

    isPreferencesVisible(): boolean {
        const preferences = this.preferences();

        return !!preferences && !preferences.hidden;
    }

    showBanner(): void {
        const banner = this.banner();

        if (!banner || !banner.hidden) {
            return;
        }

        banner.hidden = false;
        this.root()?.setAttribute('data-cc-open', 'banner');

        if (this.root()?.getAttribute('data-cc-layout') === 'modal') {
            banner.querySelector<HTMLElement>(FOCUSABLE)?.focus();
        }

        this.handlers.emit('banner-shown');
    }

    hideBanner(): void {
        const banner = this.banner();

        if (!banner || banner.hidden) {
            return;
        }

        banner.hidden = true;
        this.syncOpenState();
        this.handlers.emit('banner-hidden');
    }

    showPreferences(): void {
        const preferences = this.preferences();

        if (!preferences) {
            return;
        }

        const choices = this.handlers.choices();

        preferences.querySelectorAll<HTMLInputElement>('[data-cc-category-toggle]').forEach((toggle) => {
            if (!toggle.disabled) {
                toggle.checked = choices[toggle.getAttribute('data-cc-category-toggle') || ''] === true;
            }
        });

        if (!preferences.hidden) {
            return;
        }

        this.lastFocused = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        preferences.hidden = false;
        this.root()?.setAttribute('data-cc-open', 'preferences');
        document.documentElement.classList.add('cc-scroll-lock');
        preferences.querySelector<HTMLElement>('[role="dialog"]')?.focus();
        this.handlers.emit('preferences-shown');
    }

    hidePreferences(): void {
        const preferences = this.preferences();

        if (!preferences || preferences.hidden) {
            return;
        }

        preferences.hidden = true;
        document.documentElement.classList.remove('cc-scroll-lock');
        this.syncOpenState();

        const target = this.lastFocused && this.lastFocused.isConnected
            ? this.lastFocused
            : this.banner()?.querySelector<HTMLElement>(FOCUSABLE);
        target?.focus();
        this.lastFocused = null;
        this.handlers.emit('preferences-hidden');
    }

    hideAll(): void {
        this.hidePreferences();
        this.hideBanner();
    }

    readToggles(): Record<string, boolean> {
        const choices: Record<string, boolean> = {};

        this.preferences()?.querySelectorAll<HTMLInputElement>('[data-cc-category-toggle]').forEach((toggle) => {
            choices[toggle.getAttribute('data-cc-category-toggle') || ''] = toggle.checked;
        });

        return choices;
    }

    private syncOpenState(): void {
        const root = this.root();

        if (!root) {
            return;
        }

        if (this.isPreferencesVisible()) {
            root.setAttribute('data-cc-open', 'preferences');
        } else if (this.isBannerVisible()) {
            root.setAttribute('data-cc-open', 'banner');
        } else {
            root.removeAttribute('data-cc-open');
        }
    }

    private onClick = (event: MouseEvent): void => {
        const target = event.target instanceof Element ? event.target.closest<HTMLElement>('[data-cc-action]') : null;

        if (!target) {
            return;
        }

        switch (target.getAttribute('data-cc-action')) {
            case 'accept-all':
                event.preventDefault();
                this.handlers.acceptAll();
                break;
            case 'reject-all':
            case 'close': // Closing the banner is never consent.
                event.preventDefault();
                this.handlers.rejectAll();
                break;
            case 'save':
                event.preventDefault();
                this.handlers.save(this.readToggles());
                break;
            case 'show-preferences':
                event.preventDefault();
                this.showPreferences();
                break;
            case 'close-preferences':
                event.preventDefault();
                this.hidePreferences();
                break;
        }
    };

    private onKeydown = (event: KeyboardEvent): void => {
        if (!this.isPreferencesVisible()) {
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            this.hidePreferences();

            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        const dialog = this.preferences()?.querySelector<HTMLElement>('[role="dialog"]');

        if (!dialog) {
            return;
        }

        const focusable = Array.from(dialog.querySelectorAll<HTMLElement>(FOCUSABLE))
            .filter((element) => !element.closest('[hidden]'));

        if (focusable.length === 0) {
            return;
        }

        const first = focusable[0]!;
        const last = focusable[focusable.length - 1]!;
        const active = document.activeElement;

        if (event.shiftKey && (active === first || active === dialog)) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && active === last) {
            event.preventDefault();
            first.focus();
        } else if (!dialog.contains(active)) {
            event.preventDefault();
            first.focus();
        }
    };
}
