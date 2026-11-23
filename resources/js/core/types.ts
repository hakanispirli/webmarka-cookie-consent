export type ConsentType =
    | 'ad_storage'
    | 'ad_user_data'
    | 'ad_personalization'
    | 'analytics_storage'
    | 'functionality_storage'
    | 'personalization_storage'
    | 'security_storage';

export type ConsentValue = 'granted' | 'denied';

export type ConsentAction = 'accept_all' | 'reject_all' | 'custom';

export interface CookieDefinition {
    name: string;
    /** JavaScript regular expression source (no delimiters). */
    pattern: string | null;
}

export interface CategoryConfig {
    key: string;
    required: boolean;
    consentMode: ConsentType[];
    cookies: CookieDefinition[];
}

export interface CookieConsentConfig {
    cookie: {
        name: string;
        /** Days. */
        lifetime: number;
        domain: string | null;
        path: string;
        sameSite: string;
        /** null = automatic (Secure on HTTPS). */
        secure: boolean | null;
    };
    policyVersion: string;
    categories: CategoryConfig[];
    consentMode: {
        enabled: boolean;
        event: string;
    };
    log: {
        enabled: boolean;
        url: string | null;
        csrfToken: string | null;
    };
    ui: {
        reloadOnWithdraw: boolean;
    };
}

/** Value stored (as URL-encoded JSON) in the consent cookie. */
export interface ConsentState {
    v: 1;
    id: string;
    pv: string;
    c: Record<string, boolean>;
    ts: number;
}

export interface UpdatedEventDetail {
    state: ConsentState;
    previous: ConsentState | null;
    action: ConsentAction;
    /** Categories that were granted before and are now denied. */
    withdrawn: string[];
}

export interface CookieConsentEventMap {
    'cookie-consent:ready': { state: ConsentState | null };
    'cookie-consent:updated': UpdatedEventDetail;
    'cookie-consent:banner-shown': Record<string, never>;
    'cookie-consent:banner-hidden': Record<string, never>;
    'cookie-consent:preferences-shown': Record<string, never>;
    'cookie-consent:preferences-hidden': Record<string, never>;
}
