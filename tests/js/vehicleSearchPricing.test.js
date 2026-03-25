import test from 'node:test';
import assert from 'node:assert/strict';

import { resolveVehicleDailyPriceSeed } from '../../resources/js/utils/vehicleSearchPricing.js';

test('vehicle search pricing prefers canonical pricing fields regardless of provider', () => {
    const vehicle = {
        source: 'adobe',
        pricing: {
            price_per_day: 44.5,
            currency: 'USD',
        },
        products: [
            { total: 160, currency: 'USD' },
        ],
        tdr: 999,
    };

    assert.deepEqual(resolveVehicleDailyPriceSeed(vehicle, 4), {
        amount: 44.5,
        currency: 'USD',
    });
});

test('vehicle search pricing falls back to canonical product totals when daily price is missing', () => {
    const vehicle = {
        source: 'greenmotion',
        pricing: {
            total_price: 180,
        },
        products: [
            { total: 180, currency: 'EUR' },
        ],
    };

    assert.deepEqual(resolveVehicleDailyPriceSeed(vehicle, 3), {
        amount: 60,
        currency: 'EUR',
    });
});

test('vehicle search pricing uses generic top-level legacy values without provider-specific branching', () => {
    const vehicle = {
        source: 'favrica',
        price_per_day: '32.25',
        currency: 'EUR',
    };

    assert.deepEqual(resolveVehicleDailyPriceSeed(vehicle, 3), {
        amount: 32.25,
        currency: 'EUR',
    });
});

test('vehicle search pricing derives from canonical products even for unknown providers', () => {
    const vehicle = {
        source: 'new_provider',
        pricing: {
            total_price: 210,
        },
        products: [
            { total: 210, currency: 'GBP' },
        ],
    };

    assert.deepEqual(resolveVehicleDailyPriceSeed(vehicle, 3), {
        amount: 70,
        currency: 'GBP',
    });
});
