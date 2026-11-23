import type { CategoryConfig, ConsentAction, ConsentType, ConsentValue, CookieConsentConfig } from './types';

export const CONSENT_TYPES: ConsentType[] = [
    'ad_storage',
    'ad_user_data',
    'ad_personalization',
    'analytics_storage',
    'functionality_storage',
    'personalization_storage',
    'security_storage',
];

declare global {
    interface Window {
        dataLayer?: unknown[];
        gtag?: (...args: unknown[]) => void;
    }
}

/**
 * Maps category choices to Consent Mode v2 states. A consent type is granted
 * only if every category using it is granted; unmapped types are denied.
 */
export function consentModeFor(
    categories: CategoryConfig[],
    choices: Record<string, boolean>,
): Record<ConsentType, ConsentValue> {
    const granted: Partial<Record<ConsentType, boolean>> = {};

    for (const category of categories) {
        const isGranted = category.required || choices[category.key] === true;

        for (const type of category.consentMode) {
            granted[type] = (granted[type] ?? true) && isGranted;
        }
    }

    const result = {} as Record<ConsentType, ConsentValue>;

    for (const type of CONSENT_TYPES) {
        result[type] = granted[type] ? 'granted' : 'denied';
    }

    return result;
}

// Same shape as the official gtag() helper: gtag.js expects the Arguments object, not an array.
// eslint-disable-next-line @typescript-eslint/no-unused-vars
function pushArguments(..._args: unknown[]): void {
    // eslint-disable-next-line prefer-rest-params
    window.dataLayer!.push(arguments);
}

function gtag(...args: unknown[]): void {
    window.dataLayer = window.dataLayer || [];

    if (typeof window.gtag === 'function') {
        window.gtag(...args);
    } else {
        pushArguments(...args);
    }
}

/** Sends `gtag('consent', 'update', ...)` and a dataLayer event for GTM triggers. */
export function pushConsentUpdate(
    config: CookieConsentConfig,
    choices: Record<string, boolean>,
    action: ConsentAction,
): void {
    window.dataLayer = window.dataLayer || [];

    if (config.consentMode.enabled) {
        gtag('consent', 'update', consentModeFor(config.categories, choices));
    }

    window.dataLayer.push({
        event: config.consentMode.event,
        cookie_consent: { ...choices },
        cookie_consent_action: action,
    });
}
