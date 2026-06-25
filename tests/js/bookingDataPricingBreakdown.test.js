import test from 'node:test';
import assert from 'node:assert/strict';

import { useBookingData } from '../../resources/js/composables/useBookingData.js';

test('booking data uses explicit customer pricing metadata instead of legacy base_price totals', () => {
    const booking = {
        provider_source: 'internal',
        booking_currency: 'EUR',
        base_price: 150,
        extra_charges: 0,
        total_amount: 172.5,
        amount_paid: 22.5,
        pending_amount: 150,
        provider_metadata: {
            customer_pricing: {
                currency: 'EUR',
                vehicle_total: 115,
                extras_total: 57.5,
                grand_total: 172.5,
            },
        },
        amounts: {
            booking_currency: 'EUR',
            booking_total_amount: 172.5,
            booking_paid_amount: 22.5,
            booking_pending_amount: 150,
            booking_extra_amount: 172.5,
            vendor_currency: 'EUR',
            vendor_total_amount: 150,
            vendor_extra_amount: 50,
        },
    };

    const { pricingBreakdown } = useBookingData(booking, null, null);

    assert.equal(pricingBreakdown.value.booking.vehicleTotal, 115);
    assert.equal(pricingBreakdown.value.booking.extrasTotal, 57.5);
    assert.equal(pricingBreakdown.value.booking.grandTotal, 172.5);
    assert.equal(pricingBreakdown.value.payment.paidNow, 22.5);
    assert.equal(pricingBreakdown.value.payment.dueOnArrival, 150);
});

test('booking data prefers confirmed provider pricing for deposit and excess over stale benefits', () => {
    const booking = {
        provider_source: 'surprice',
        booking_currency: 'EUR',
        total_amount: 294.32,
        amount_paid: 38.39,
        pending_amount: 255.93,
        provider_metadata: {
            currency: 'EUR',
            benefits: {
                deposit_amount: 1100,
                excess_amount: 1100,
                excess_theft_amount: 1100,
                deposit_currency: 'EUR',
            },
            provider_pricing: {
                deposit_amount: 250,
                excess_amount: 0,
                excess_theft_amount: 0,
                deposit_currency: 'EUR',
            },
            customer_pricing: {
                vehicle_total: 246.02,
                extras_total: 48.3,
                grand_total: 294.32,
            },
        },
        amounts: {
            booking_currency: 'EUR',
            booking_total_amount: 294.32,
            booking_paid_amount: 38.39,
            booking_pending_amount: 255.93,
            vendor_currency: 'EUR',
            vendor_total_amount: 255.93,
            vendor_extra_amount: 42,
        },
    };

    const { pricingBreakdown } = useBookingData(booking, null, null);

    assert.deepEqual(pricingBreakdown.value.deposit, { amount: 250, currency: 'EUR' });
    assert.deepEqual(pricingBreakdown.value.excess, { amount: 0, currency: 'EUR' });
    assert.deepEqual(pricingBreakdown.value.excessTheft, { amount: 0, currency: 'EUR' });
});
