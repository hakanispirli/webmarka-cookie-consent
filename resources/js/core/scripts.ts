const ACTIVATED = 'data-cc-activated';

/**
 * Whether an element's `data-cc-category` requirement is satisfied.
 * Categories are space separated; `data-cc-require="any"` needs only one.
 */
export function isAllowed(element: Element, hasConsent: (category: string) => boolean): boolean {
    const categories = (element.getAttribute('data-cc-category') || '').split(/\s+/).filter(Boolean);

    if (categories.length === 0) {
        return false;
    }

    return element.getAttribute('data-cc-require') === 'any'
        ? categories.some(hasConsent)
        : categories.every(hasConsent);
}

/**
 * Activates inert elements whose categories are granted:
 *  - <script type="text/plain" data-cc-category="analytics">  -> executed
 *  - <iframe data-cc-src="..." data-cc-category="marketing">  -> src set
 *
 * Returns the number of activated elements.
 */
export function activateElements(root: ParentNode, hasConsent: (category: string) => boolean): number {
    let count = 0;

    root.querySelectorAll<HTMLScriptElement>(`script[type="text/plain"][data-cc-category]:not([${ACTIVATED}])`).forEach((original) => {
        if (!isAllowed(original, hasConsent)) {
            return;
        }

        const script = document.createElement('script');

        for (const attribute of Array.from(original.attributes)) {
            if (attribute.name !== 'type') {
                script.setAttribute(attribute.name, attribute.value);
            }
        }

        // The nonce attribute is hidden from the DOM after parsing; copy the property.
        if (original.nonce) {
            script.nonce = original.nonce;
        }

        script.type = original.getAttribute('data-cc-type') || 'text/javascript';
        script.setAttribute(ACTIVATED, '');

        if (original.src) {
            // Keep execution order of external scripts unless async was requested.
            script.async = original.hasAttribute('async');
        } else {
            script.text = original.text;
        }

        original.setAttribute(ACTIVATED, '');
        original.replaceWith(script);
        count++;
    });

    root.querySelectorAll<HTMLElement>(`[data-cc-src][data-cc-category]:not([${ACTIVATED}])`).forEach((element) => {
        if (!isAllowed(element, hasConsent)) {
            return;
        }

        element.setAttribute('src', element.getAttribute('data-cc-src') || '');
        element.setAttribute(ACTIVATED, '');
        element.removeAttribute('hidden');
        count++;
    });

    return count;
}
