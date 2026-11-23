import type { CookieConsentConfig, CookieDefinition } from './types';

export function readCookie(name: string): string | null {
    const prefix = `${name}=`;
    const parts = document.cookie ? document.cookie.split('; ') : [];

    for (const part of parts) {
        if (part.startsWith(prefix)) {
            return part.substring(prefix.length);
        }
    }

    return null;
}

export function writeCookie(config: CookieConsentConfig['cookie'], value: string): void {
    const secure = config.secure ?? window.location.protocol === 'https:';
    const expires = new Date(Date.now() + config.lifetime * 864e5).toUTCString();
    let cookie = `${config.name}=${encodeURIComponent(value)}; expires=${expires}; path=${config.path || '/'}; SameSite=${config.sameSite || 'Lax'}`;

    if (config.domain) {
        cookie += `; domain=${config.domain}`;
    }

    if (secure) {
        cookie += '; Secure';
    }

    document.cookie = cookie;
}

export function cookieNames(): string[] {
    if (!document.cookie) {
        return [];
    }

    return document.cookie.split('; ').map((part) => {
        const index = part.indexOf('=');

        return index === -1 ? part : part.substring(0, index);
    });
}

/**
 * Domains a cookie may have been set on: host-only, the host itself and
 * every parent domain (e.g. www.example.com, .example.com).
 */
export function candidateDomains(hostname: string): Array<string | null> {
    const domains: Array<string | null> = [null];
    const labels = hostname.split('.');

    for (let i = 0; i < labels.length - 1; i++) {
        const domain = labels.slice(i).join('.');
        domains.push(domain, `.${domain}`);
    }

    return domains;
}

export function deleteCookie(name: string, hostname = window.location.hostname): void {
    const expired = 'expires=Thu, 01 Jan 1970 00:00:00 GMT';

    for (const domain of candidateDomains(hostname)) {
        document.cookie = `${name}=; ${expired}; path=/${domain ? `; domain=${domain}` : ''}`;
    }
}

export function matchesDefinition(name: string, definition: CookieDefinition): boolean {
    if (definition.pattern) {
        try {
            return new RegExp(definition.pattern).test(name);
        } catch {
            return false;
        }
    }

    return name === definition.name;
}

/** Deletes every existing cookie matching one of the definitions. */
export function deleteMatchingCookies(definitions: CookieDefinition[]): string[] {
    const deleted: string[] = [];

    for (const name of cookieNames()) {
        if (definitions.some((definition) => matchesDefinition(name, definition))) {
            deleteCookie(name);
            deleted.push(name);
        }
    }

    return deleted;
}
