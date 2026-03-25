import test from 'node:test';
import assert from 'node:assert/strict';

import { normalizeProtectionPlans } from '../../resources/js/features/search/utils/normalizeProtectionPlans.js';

const identity = (amount) => amount;

test('normalizes greenmotion products into canonical plan format', () => {
    const plans = normalizeProtectionPlans({
        vehicle: {
            source: 'greenmotion',
            products: [
                { type: 'BAS', total: 90, currency: 'EUR', excess: 1200, deposit: 500, fuelpolicy: 'FF' },
                { type: 'PRE', total: 150, currency: 'EUR', excess: 0, deposit: 200, fuelpolicy: 'FF' },
            ],
            currency: 'EUR',
        },
        rentalDays: 3,
        selectedId: 'BAS',
        convertPrice: identity,
    });

    assert.equal(plans.length, 2);
    assert.equal(plans[0].id, 'BAS');
    assert.equal(plans[0].name, 'Basic');
    assert.equal(plans[0].dailyPrice, 30);
    assert.equal(plans[0].totalPrice, 90);
    assert.equal(plans[0].isSelected, true);
    assert.equal(plans[1].id, 'PRE');
    assert.equal(plans[1].isSelected, false);
});

test('normalizes locauto plans with basic + protections', () => {
    const plans = normalizeProtectionPlans({
        vehicle: {
            source: 'locauto_rent',
            price_per_day: 40,
            total_price: 120,
            currency: 'EUR',
            extras: [
                { code: '147', description: 'Smart Cover', amount: 15 },
                { code: '136', description: "Don't Worry", amount: 25 },
            ],
        },
        rentalDays: 3,
        selectedId: null,
        convertPrice: identity,
    });

    assert.equal(plans.length, 3); // Basic + 2 protections
    assert.equal(plans[0].id, 'BAS');
    assert.equal(plans[0].name, 'Basic Coverage');
    assert.equal(plans[0].isSelected, true);
    assert.equal(plans[0].dailyPrice, 40);
    assert.equal(plans[1].id, '147');
    assert.equal(plans[1].dailyPrice, 55); // 40 + 15
});

test('normalizes adobe plans with base + protections', () => {
    const plans = normalizeProtectionPlans({
        vehicle: {
            source: 'adobe',
            tdr: 200,
            ldw: 50,
            spp: 80,
        },
        rentalDays: 4,
        selectedId: null,
        convertPrice: identity,
    });

    assert.equal(plans.length, 3); // Basic + LDW + SPP
    assert.equal(plans[0].id, 'BAS');
    assert.equal(plans[0].dailyPrice, 50); // 200/4
    assert.equal(plans[0].totalPrice, 200);
    assert.equal(plans[1].id, 'LDW');
    assert.equal(plans[1].totalPrice, 250); // 200 + 50
    assert.equal(plans[2].id, 'SPP');
    assert.equal(plans[2].totalPrice, 280); // 200 + 80
});

test('normalizes internal vendor plans', () => {
    const plans = normalizeProtectionPlans({
        vehicle: {
            source: 'internal',
            pricing: { price_per_day: 30, deposit_amount: 500 },
            booking_context: {
                provider_payload: {
                    vendorPlans: [
                        { id: 1, plan_type: 'PRE', price: 45, features: '["Zero excess","Free cancellation"]' },
                    ],
                },
            },
        },
        rentalDays: 5,
        selectedId: null,
        convertPrice: identity,
    });

    assert.equal(plans.length, 2); // Basic + PRE
    assert.equal(plans[0].id, 'BAS');
    assert.equal(plans[0].dailyPrice, 30);
    assert.equal(plans[0].totalPrice, 150); // 30*5
    assert.equal(plans[1].id, '1');
    assert.equal(plans[1].dailyPrice, 45);
    assert.equal(plans[1].benefits.length, 2);
});

test('returns empty array for providers without plans', () => {
    const plans = normalizeProtectionPlans({
        vehicle: { source: 'renteon', price_per_day: 40 },
        rentalDays: 3,
        selectedId: null,
        convertPrice: identity,
    });

    assert.equal(plans.length, 0);
});
