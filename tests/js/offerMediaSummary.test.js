import test from 'node:test';
import assert from 'node:assert/strict';

import { buildOfferMediaSummary } from '../../resources/js/features/skyscanner/utils/offerMediaSummary.js';

test('buildOfferMediaSummary returns compact offer facts for the media side panel', () => {
    const items = buildOfferMediaSummary({
        supplierName: 'Green Motion',
        pickupOffice: 'Dubai Airport',
        transmission: 'automatic',
        fuelType: 'petrol',
        seats: 5,
        mileagePolicy: 'Unlimited mileage',
        cancellation: {
            available: true,
            daysBeforePickup: 2,
        },
    });

    assert.deepEqual(items, [
        { icon: 'badge', label: 'Supplier', value: 'Green Motion' },
        { icon: 'pin', label: 'Pickup', value: 'Dubai Airport' },
        { icon: 'gauge', label: 'Specs', value: 'automatic • petrol • 5 seats' },
        { icon: 'road', label: 'Mileage', value: 'Unlimited mileage' },
        { icon: 'shield', label: 'Cancellation', value: 'Free up to 2 days before pickup' },
    ]);
});
