import { describe, expect, it } from 'vitest';
import { consentModeFor } from '../resources/js/core/consent-mode';
import { makeConfig } from './helpers';

describe('consentModeFor', () => {
    const { categories } = makeConfig();

    it('denies everything except required categories by default', () => {
        expect(consentModeFor(categories, {})).toEqual({
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
            analytics_storage: 'denied',
            functionality_storage: 'denied',
            personalization_storage: 'denied',
            security_storage: 'granted',
        });
    });

    it('grants the v2 ad types together with marketing', () => {
        const result = consentModeFor(categories, { marketing: true });

        expect(result.ad_storage).toBe('granted');
        expect(result.ad_user_data).toBe('granted');
        expect(result.ad_personalization).toBe('granted');
        expect(result.analytics_storage).toBe('denied');
    });

    it('grants a shared type only when every category using it is granted', () => {
        const shared = [
            { key: 'a', required: false, consentMode: ['ad_storage' as const], cookies: [] },
            { key: 'b', required: false, consentMode: ['ad_storage' as const], cookies: [] },
        ];

        expect(consentModeFor(shared, { a: true }).ad_storage).toBe('denied');
        expect(consentModeFor(shared, { a: true, b: true }).ad_storage).toBe('granted');
    });
});
