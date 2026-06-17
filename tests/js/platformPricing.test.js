import test from 'node:test';
import assert from 'node:assert/strict';

import {
    computeBookingChargeBreakdown,
    resolveEffectiveProviderMarkupRate,
    shouldUseCommissionOnlyForVehicle,
} from '../../resources/js/utils/platformPricing.js';

test('uses commission-only pay-now totals when the booking flow charges markup upfront', () => {
    assert.deepEqual(
        computeBookingChargeBreakdown({
            netTotal: 100,
            markupRate: 0.15,
            depositPercentage: 15,
            useCommissionOnly: true,
        }),
        {
            grandTotal: 115,
            payableAmount: 15,
            pendingAmount: 100,
        },
    );
});

test('uses deposit-percentage pay-now totals when commission-only charging is disabled', () => {
    assert.deepEqual(
        computeBookingChargeBreakdown({
            netTotal: 100,
            markupRate: 0,
            depositPercentage: 20,
            useCommissionOnly: false,
        }),
        {
            grandTotal: 100,
            payableAmount: 20,
            pendingAmount: 80,
        },
    );
});

test('bypasses provider markup for partner quote vehicles with public prices', () => {
    const vehicle = {
        source: 'greenmotion',
        partner_supplier_name: 'Vrooem',
    };

    assert.equal(resolveEffectiveProviderMarkupRate(vehicle, 0.15), 0);
    assert.equal(shouldUseCommissionOnlyForVehicle(vehicle, 0.15), false);

    assert.deepEqual(
        computeBookingChargeBreakdown({
            netTotal: 312.45,
            markupRate: resolveEffectiveProviderMarkupRate(vehicle, 0.15),
            depositPercentage: 15,
            useCommissionOnly: shouldUseCommissionOnlyForVehicle(vehicle, 0.15),
        }),
        {
            grandTotal: 312.45,
            payableAmount: 46.87,
            pendingAmount: 265.58,
        },
    );
});

test('keeps provider markup for ordinary provider checkout vehicles', () => {
    const vehicle = {
        source: 'greenmotion',
    };

    assert.equal(resolveEffectiveProviderMarkupRate(vehicle, 0.15), 0.15);
    assert.equal(shouldUseCommissionOnlyForVehicle(vehicle, 0.15), true);
});
