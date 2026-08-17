import test from 'node:test';
import assert from 'node:assert/strict';

import { getCapturedPaymentReview } from '../../resources/js/utils/bookingPaymentPresentation.js';

test('returns the exact two-decimal Stripe capture for customer review copy', () => {
    assert.deepEqual(getCapturedPaymentReview({
        provider_metadata: {
            amount_mismatch: {
                charged_minor: 10525,
                charged_currency: 'usd',
            },
        },
    }, () => 2), {
        amount: 105.25,
        currency: 'USD',
        minorUnit: 2,
    });
});

test('honours zero-decimal currencies', () => {
    assert.deepEqual(getCapturedPaymentReview({
        provider_metadata: {
            amount_mismatch: {
                charged_minor: 1250,
                charged_currency: 'JPY',
            },
        },
    }, () => 0), {
        amount: 1250,
        currency: 'JPY',
        minorUnit: 0,
    });
});

test('returns null when no trustworthy captured amount is available', () => {
    assert.equal(getCapturedPaymentReview({}), null);
    assert.equal(getCapturedPaymentReview({
        provider_metadata: {
            amount_mismatch: {
                charged_minor: -1,
                charged_currency: 'EUR',
            },
        },
    }), null);
});
