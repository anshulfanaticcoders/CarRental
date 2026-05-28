const RETURNABLE_SECTIONS = new Set(['s', 'booking']);
const LOCALES = new Set(['en', 'fr', 'nl', 'es', 'ar']);

export const isAuthReturnUrl = (url) => {
    if (!url || typeof url !== 'string') {
        return false;
    }

    try {
        const origin = window.location.origin;
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
    const params = { locale };

    if (isAuthReturnUrl(pageUrl)) {
        params.redirect = pageUrl;
    }

    return route('login', params);
};
