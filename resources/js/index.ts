/**
 * ESM entry: import { CookieConsentManager } from '.../dist/cookie-consent.esm.js'
 */
export { CookieConsentManager } from './core/manager';
export { consentModeFor, CONSENT_TYPES } from './core/consent-mode';
export { activateElements, isAllowed } from './core/scripts';
export { boot, readConfig } from './boot';
export type * from './core/types';
