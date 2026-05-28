const RETURNABLE_SECTIONS = new Set(['s', 'booking']);
const LOCALES = new Set(['en', 'fr', 'nl', 'es', 'ar']);

const fallbackOrigin = 'https://vrooem.local';

const resolveOrigin = () => {
    if (typeof window !== 'undefined' && window.location?.origin) {
        return window.location.origin;
    }

    return fallbackOrigin;
};

const resolveLocale = (locale) => {
    return LOCALES.has(locale) ? locale : 'en';
};

const buildLoginPath = (locale, redirect) => {
    const path = `/${resolveLocale(locale)}/login`;

    if (!redirect) {
        return path;
    }

    const query = new URLSearchParams({ redirect });

    return `${path}?${query.toString()}`;
};

export const isAuthReturnUrl = (url) => {
    if (!url || typeof url !== 'string') {
        return false;
    }

    try {
        if (typeof window === 'undefined' && (/^[a-z][a-z\d+\-.]*:\/\//i.test(url) || url.startsWith('//'))) {
            return false;
        }

        const origin = resolveOrigin();
        const parsed = new URL(url, origin);

        if (parsed.origin !== origin) {
            return false;
        }

        const segments = parsed.pathname.split('/').filter(Boolean);

        return segments.length >= 2
            && LOCALES.has(segments[0])
            && RETURNABLE_SECTIONS.has(segments[1]);
    } catch {
        return false;
    }
};

export const loginHrefForPage = (locale, pageUrl) => {
    const params = { locale: resolveLocale(locale) };

    if (isAuthReturnUrl(pageUrl)) {
        params.redirect = pageUrl;
    }

    const routeFn = typeof globalThis !== 'undefined' && typeof globalThis.route === 'function'
        ? globalThis.route
        : null;

    if (routeFn) {
        return routeFn('login', params);
    }

    return buildLoginPath(params.locale, params.redirect);
};
