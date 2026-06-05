import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { findLocalizedPage, translatedPageSlug } from '@/utils/localizedPages';

const LOCALES = new Set(['en', 'fr', 'nl', 'es', 'ar']);
const DEFAULT_COUNTRY = 'us';

const routeOrPath = (name, params, fallback) => {
    try {
        if (typeof route === 'function' && route().has(name)) {
            return route(name, params);
        }
    } catch {
        // Fall through to a deterministic path for SSR/tests.
    }

    return fallback;
};

const appendSearchAndHash = (path, url) => {
    return `${path}${url.search || ''}${url.hash || ''}`;
};

const localePath = (locale, suffix = '') => {
    const normalizedSuffix = suffix ? `/${String(suffix).replace(/^\/+/, '')}` : '';

    return `/${locale}${normalizedSuffix}`;
};

const pageRoute = (locale, slug) => {
    return routeOrPath(
        'pages.show',
        { locale, slug },
        localePath(locale, `page/${slug}`)
    );
};

const blogListRoute = (locale, country) => {
    return routeOrPath(
        'blog',
        { locale, country },
        localePath(locale, `${country}/blog`)
    );
};

const blogShowRoute = (locale, country, slug) => {
    return routeOrPath(
        'blog.show',
        { locale, country, blog: slug },
        localePath(locale, `${country}/blog/${slug}`)
    );
};

export const useLocalizedRoutes = () => {
    const page = usePage();
    const currentLocale = computed(() => page.props.locale || 'en');
    const currentCountry = computed(() => String(page.props.country || DEFAULT_COUNTRY).toLowerCase());
    const pages = computed(() => page.props.pages || {});

    const pageSlug = (pageIdentity, locale = currentLocale.value) => {
        return translatedPageSlug(pages.value, pageIdentity, locale);
    };

    const pageHref = (pageIdentity, locale = currentLocale.value) => {
        return pageRoute(locale, pageSlug(pageIdentity, locale));
    };

    const welcomeHref = (locale = currentLocale.value) => {
        return routeOrPath('welcome', { locale }, localePath(locale));
    };

    const blogHref = (locale = currentLocale.value, country = currentCountry.value) => {
        return blogListRoute(locale, country);
    };

    const faqHref = (locale = currentLocale.value) => {
        return routeOrPath('faq.show', { locale }, localePath(locale, 'faq'));
    };

    const affiliateRegisterHref = (locale = currentLocale.value) => {
        return routeOrPath('affiliate.register', { locale }, localePath(locale, 'affiliate/register'));
    };

    const resolveLanguageTargetUrl = (newLocale) => {
        if (!LOCALES.has(newLocale)) {
            return page.url || localePath(currentLocale.value);
        }

        if (typeof window === 'undefined') {
            return welcomeHref(newLocale);
        }

        const currentUrl = new URL(window.location.href);
        const segments = currentUrl.pathname.split('/').filter(Boolean);
        const country = String(segments[1] || currentCountry.value || DEFAULT_COUNTRY).toLowerCase();
        let targetUrl = null;

        if (segments[1] === 'page' && segments[2]) {
            const targetPage = findLocalizedPage(pages.value, segments[2]);
            if (targetPage) {
                targetUrl = pageHref(segments[2], newLocale);
            }
        }

        if (!targetUrl && segments.length === 2) {
            const targetPage = findLocalizedPage(pages.value, segments[1]);
            if (targetPage) {
                targetUrl = pageHref(segments[1], newLocale);
            }
        }

        if (!targetUrl && segments[2] === 'blog' && segments[3] && page.props.blog?.translations) {
            const translatedBlog = page.props.blog.translations.find((translation) => {
                return translation?.locale === newLocale && translation?.slug;
            });

            targetUrl = translatedBlog
                ? blogShowRoute(newLocale, country, translatedBlog.slug)
                : blogListRoute(newLocale, country);
        }

        if (!targetUrl && segments[2] === 'blog') {
            targetUrl = blogListRoute(newLocale, country);
        }

        if (!targetUrl && segments[1] === 'faq') {
            targetUrl = faqHref(newLocale);
        }

        if (!targetUrl && segments[1] === 'affiliate' && segments[2] === 'register') {
            targetUrl = affiliateRegisterHref(newLocale);
        }

        if (!targetUrl && segments[1] === 'business' && segments[2] === 'register') {
            targetUrl = affiliateRegisterHref(newLocale);
        }

        if (!targetUrl) {
            segments[0] = newLocale;
            targetUrl = `/${segments.join('/')}`;
        }

        return appendSearchAndHash(targetUrl, currentUrl);
    };

    return {
        currentLocale,
        currentCountry,
        pageSlug,
        pageHref,
        welcomeHref,
        blogHref,
        faqHref,
        affiliateRegisterHref,
        resolveLanguageTargetUrl,
    };
};
