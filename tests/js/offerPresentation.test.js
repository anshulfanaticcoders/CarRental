import test from 'node:test';
import assert from 'node:assert/strict';

import {
    ALTERNATIVES_INITIAL_COUNT,
    ALTERNATIVES_REVEAL_STEP,
    buildOfferSpecFacts,
    buildOfferHistoryState,
    normalizeOfferPaymentPercentage,
    resolveOfferPartner,
    resolveOfferPaymentSplit,
    revealMoreAlternatives,
} from '../../resources/js/features/offer-results/utils/offerPresentation.js';

test('offer history state preserves Inertia page metadata', () => {
    const inertiaState = {
        component: 'OfferResults',
        props: { quote: { quote_id: 'quote-123' } },
        url: '/en/offers/quote-123',
        version: 'asset-version',
    };

    const historyState = buildOfferHistoryState(inertiaState, 'extras');

    assert.deepEqual(historyState, { ...inertiaState, bookingStep: 'extras' });
    assert.notEqual(historyState, inertiaState);
    assert.equal(inertiaState.bookingStep, undefined);
});

test('resolveOfferPartner reads Trabber from the trabber/offers page url', () => {
    assert.equal(resolveOfferPartner('/en/trabber/offers/abc123').key, 'trabber');
    assert.equal(resolveOfferPartner('/nl/trabber/offers/abc123').label, 'Trabber');
});

test('resolveOfferPartner reads Skyscanner from a plain offers page url', () => {
    assert.equal(resolveOfferPartner('/es/offers/abc123').key, 'skyscanner');
    assert.equal(resolveOfferPartner('/fr/offers/abc123').label, 'Skyscanner');
});

test('resolveOfferPartner ignores query strings, fragments and absolute origins', () => {
    assert.equal(resolveOfferPartner('/fr/trabber/offers/x?utm_source=trabber#top').key, 'trabber');
    assert.equal(resolveOfferPartner('https://vrooem.com/nl/trabber/offers/x').key, 'trabber');
    assert.equal(resolveOfferPartner('https://vrooem.com/nl/offers/x').key, 'skyscanner');
});

test('resolveOfferPartner falls back to a neutral partner off the offer routes', () => {
    assert.equal(resolveOfferPartner('/en/search').key, 'partner');
    assert.equal(resolveOfferPartner('/en/offers-archive/x').key, 'partner');
    assert.equal(resolveOfferPartner('').key, 'partner');
    assert.equal(resolveOfferPartner(null).key, 'partner');
});

test('resolveOfferPartner exposes a translation key so no label ships untranslated', () => {
    assert.equal(resolveOfferPartner('/en/trabber/offers/x').translationKey, 'partner_source_trabber');
    assert.equal(resolveOfferPartner('/en/offers/x').translationKey, 'partner_source_skyscanner');
    assert.equal(resolveOfferPartner('/en/search').translationKey, 'partner_source_generic');
});

test('alternatives start at three and reveal three at a time', () => {
    assert.equal(ALTERNATIVES_INITIAL_COUNT, 3);
    assert.equal(ALTERNATIVES_REVEAL_STEP, 3);
    assert.equal(revealMoreAlternatives(3, 12), 6);
    assert.equal(revealMoreAlternatives(6, 12), 9);
});

test('revealMoreAlternatives never advertises more alternatives than exist', () => {
    assert.equal(revealMoreAlternatives(3, 4), 4);
    assert.equal(revealMoreAlternatives(3, 3), 3);
    assert.equal(revealMoreAlternatives(3, 0), 0);
});

test('payment percentage normalization keeps valid values including zero', () => {
    assert.equal(normalizeOfferPaymentPercentage(15), 15);
    assert.equal(normalizeOfferPaymentPercentage(12.5), 12.5);
    assert.equal(normalizeOfferPaymentPercentage('20'), 20);
    assert.equal(normalizeOfferPaymentPercentage(0), 0);
    assert.equal(normalizeOfferPaymentPercentage(100), 100);
});

test('payment percentage normalization rejects missing and out-of-range values', () => {
    for (const invalid of [null, undefined, '', ' ', 'abc', NaN, Infinity, -1, 101]) {
        assert.equal(normalizeOfferPaymentPercentage(invalid), null, `expected null for ${String(invalid)}`);
    }
});

test('payment split is unavailable when the percentage is unavailable', () => {
    assert.equal(resolveOfferPaymentSplit(300, null), null);
    assert.equal(resolveOfferPaymentSplit(null, 15), null);
});

test('payment split uses the supplied percentage and rounds to cents', () => {
    assert.deepEqual(resolveOfferPaymentSplit(300, 15), { payNow: 45, payLater: 255 });
    assert.deepEqual(resolveOfferPaymentSplit(100, 0), { payNow: 0, payLater: 100 });
    assert.deepEqual(resolveOfferPaymentSplit(233.33, 12.5), { payNow: 29.17, payLater: 204.16 });
});

test('spec facts keep decision order and drop blanks', () => {
    const facts = buildOfferSpecFacts({
        class: 'ECMR',
        seats: '5',
        bags: '',
        transmission: 'Auto',
        fuel: null,
        airConditioning: 'Included',
        mileage: 'Unlimited mileage',
    });

    assert.deepEqual(facts.map((fact) => fact.key), ['class', 'seats', 'transmission', 'air_conditioning', 'mileage']);
    assert.equal(facts[0].value, 'ECMR');
});

test('spec facts never repeat the same value twice', () => {
    const facts = buildOfferSpecFacts({ class: 'Compact', seats: '5', fuel: 'compact', mileage: 'Unlimited mileage' });

    assert.deepEqual(facts.map((fact) => fact.key), ['class', 'seats', 'mileage']);
});
