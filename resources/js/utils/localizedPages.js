const DEFAULT_LOCALE = 'en';

const normalizeSlug = (value) => {
    if (value === null || value === undefined) {
        return '';
    }

    return String(value).trim().replace(/^\/+|\/+$/g, '');
};

const pageValues = (pages) => {
    if (!pages) {
        return [];
    }

    if (Array.isArray(pages)) {
        return pages;
    }

    return Object.values(pages);
};

const pageTranslations = (page) => {
    return Array.isArray(page?.translations) ? page.translations : [];
};

export const findLocalizedPage = (pages, pageIdentity) => {
    const identity = normalizeSlug(pageIdentity);

    if (!identity) {
        return null;
    }

    return pageValues(pages).find((page) => {
        if (!page) {
            return false;
        }

        const directSlugs = [
            page.slug,
            page.custom_slug,
            page.customSlug,
        ].map(normalizeSlug);

        if (directSlugs.includes(identity)) {
            return true;
        }

        return pageTranslations(page).some((translation) => {
            return normalizeSlug(translation?.slug) === identity;
        });
    }) || null;
};

export const translatedPageSlug = (pages, pageIdentity, locale, fallbackLocale = DEFAULT_LOCALE) => {
    const targetPage = findLocalizedPage(pages, pageIdentity);

    if (!targetPage) {
        return normalizeSlug(pageIdentity);
    }

    const translations = pageTranslations(targetPage);
    const localized = translations.find((translation) => {
        return translation?.locale === locale && normalizeSlug(translation?.slug) !== '';
    });

    if (localized) {
        return normalizeSlug(localized.slug);
    }

    const fallback = translations.find((translation) => {
        return translation?.locale === fallbackLocale && normalizeSlug(translation?.slug) !== '';
    });

    return normalizeSlug(fallback?.slug || pageIdentity);
};
