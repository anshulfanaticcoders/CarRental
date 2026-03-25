import test from 'node:test';
import assert from 'node:assert/strict';

import { resolveSearchCardDisplayPrice } from '../../resources/js/features/search/utils/searchCardDisplayPrice.js';

test('search card display price resolves GM package total per day', () => {
    const value = resolveSearchCardDisplayPrice({
        vehicle: {
            source: 'greenmotion',
            products: [{ type: 'BAS', total: 180, currency: 'EUR' }],
            currency: 'EUR',
        },
        rentalDays: 3,
        selectedPackage: 'BAS',
        canConvertFrom: () => true,
        convertRentalPrice: (amount) => amount,
    });

    assert.equal(value, '60.00');
});

test('search card display price resolves locauto with selected protection amount', () => {
    const value = resolveSearchCardDisplayPrice({
        vehicle: {
            source: 'locauto_rent',
            price_per_day: 50,
            currency: 'EUR',
        },
        selectedProtection: { amount: 12.5 },
        canConvertFrom: () => true,
        convertRentalPrice: (amount) => amount,
    });

    assert.equal(value, '62.50');
});

test('search card display price resolves adobe from base total and selected protection', () => {
    const value = resolveSearchCardDisplayPrice({
        vehicle: {
            source: 'adobe',
        },
        rentalDays: 4,
        baseTotal: 100,
        selectedProtection: { amount: 20 },
        canConvertFrom: () => true,
        convertRentalPrice: (amount) => amount,
    });

    assert.equal(value, '30.00');
});

test('search card display price resolves renteon from top-level daily price', () => {
    const value = resolveSearchCardDisplayPrice({
        vehicle: {
            source: 'renteon',
            price_per_day: 40,
            currency: 'EUR',
        },
        canConvertFrom: () => true,
        convertRentalPrice: (amount) => amount,
    });

    assert.equal(value, '40.00');
});

test('search card display price returns null for generic providers using the search results slot', () => {
    const value = resolveSearchCardDisplayPrice({
        vehicle: {
            source: 'xdrive',
            price_per_day: 40,
            currency: 'EUR',
        },
        canConvertFrom: () => true,
        convertRentalPrice: (amount) => amount,
    });

    assert.equal(value, null);
});
