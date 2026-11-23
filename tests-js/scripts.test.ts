import { beforeEach, describe, expect, it } from 'vitest';
import { activateElements, isAllowed } from '../resources/js/core/scripts';

// jsdom runs scripts in its own realm; record execution through the shared DOM.
const ran = (): string[] => (document.documentElement.getAttribute('data-ran') || '').split(' ').filter(Boolean);
const record = (name: string): string => `document.documentElement.setAttribute('data-ran', ((document.documentElement.getAttribute('data-ran') || '') + ' ${name}').trim())`;

describe('script activation', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
        document.documentElement.removeAttribute('data-ran');
    });

    it('requires every listed category by default and any with data-cc-require="any"', () => {
        const el = document.createElement('script');
        el.setAttribute('data-cc-category', 'analytics marketing');

        expect(isAllowed(el, (c) => c === 'analytics')).toBe(false);

        el.setAttribute('data-cc-require', 'any');
        expect(isAllowed(el, (c) => c === 'analytics')).toBe(true);
    });

    it('executes text/plain scripts only for granted categories', () => {
        document.body.innerHTML = `
            <script type="text/plain" data-cc-category="analytics">${record('analytics')}</script>
            <script type="text/plain" data-cc-category="marketing">${record('marketing')}</script>`;

        expect(activateElements(document, (c) => c === 'analytics')).toBe(1);
        expect(ran()).toEqual(['analytics']);

        // Not executed twice.
        activateElements(document, (c) => c === 'analytics');
        expect(ran()).toEqual(['analytics']);

        const marketing = document.querySelector('script[data-cc-category="marketing"]');
        expect(marketing?.getAttribute('type')).toBe('text/plain');
    });

    it('keeps attributes and uses data-cc-type', () => {
        document.body.innerHTML = `<script type="text/plain" data-cc-type="module" data-cc-category="analytics" id="x" src="/a.js"></script>`;

        activateElements(document, () => true);

        const script = document.getElementById('x') as HTMLScriptElement;
        expect(script.type).toBe('module');
        expect(script.getAttribute('src')).toBe('/a.js');
        expect(script.hasAttribute('data-cc-activated')).toBe(true);
    });

    it('sets data-cc-src on iframes after consent', () => {
        document.body.innerHTML = `<iframe data-cc-src="https://www.youtube-nocookie.com/embed/x" data-cc-category="marketing"></iframe>`;
        const iframe = document.querySelector('iframe')!;

        activateElements(document, () => false);
        expect(iframe.hasAttribute('src')).toBe(false);

        activateElements(document, () => true);
        expect(iframe.getAttribute('src')).toBe('https://www.youtube-nocookie.com/embed/x');
    });
});
