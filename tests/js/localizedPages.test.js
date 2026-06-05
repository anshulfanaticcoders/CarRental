import assert from 'node:assert/strict';
import test from 'node:test';
import { findLocalizedPage, translatedPageSlug } from '../../resources/js/utils/localizedPages.js';

const pages = {
    'terms-conditions': {
        slug: 'terms-conditions',
        custom_slug: 'terms-and-conditions',
        translations: [
            { locale: 'en', slug: 'terms-conditions' },
            { locale: 'fr', slug: 'conditions-generales' },
            { locale: 'nl', slug: 'algemene-voorwaarden' },
            { locale: 'es', slug: 'terminos-y-condiciones' },
            { locale: 'ar', slug: 'alshrwt-walahkam' },
        ],
    },
    'privacy-policy': {
        slug: 'privacy-policy',
        custom_slug: 'privacy-policy',
        translations: [
            { locale: 'en', slug: 'privacy-policy' },
            { locale: 'es', slug: 'politica-de-privacidad' },
        ],
    },
};

test('matches pages by custom slug and returns the requested locale slug', () => {
    assert.equal(translatedPageSlug(pages, 'terms-and-conditions', 'es'), 'terminos-y-condiciones');
});

test('matches pages by any translated slug', () => {
    const page = findLocalizedPage(pages, 'algemene-voorwaarden');

    assert.equal(page.slug, 'terms-conditions');
    assert.equal(translatedPageSlug(pages, 'algemene-voorwaarden', 'fr'), 'conditions-generales');
});

test('falls back to English slug when a locale translation is missing', () => {
    assert.equal(translatedPageSlug(pages, 'privacy-policy', 'ar'), 'privacy-policy');
});
