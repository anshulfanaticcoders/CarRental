import test from 'node:test';
import assert from 'node:assert/strict';

import { computeBookingChargeBreakdown } from '../../resources/js/utils/platformPricing.js';

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
