import test from 'node:test';
import assert from 'node:assert/strict';

import { resolveOfferMapDetails } from '../../resources/js/features/skyscanner/utils/offerMapDetails.js';

test('resolveOfferMapDetails returns compact map data for pickup-only offers', () => {
    const details = resolveOfferMapDetails(
        { name: 'Dubai Airport', latitude: 25.250291, longitude: 55.345171 },
        { name: 'Dubai Airport', latitude: 25.250291, longitude: 55.345171 },
    );

    assert.equal(details.hasMap, true);
    assert.equal(details.hasPickup, true);
    assert.equal(details.hasDropoff, false);
    assert.deepEqual(details.pickup, {
        latitude: 25.250291,
        longitude: 55.345171,
        name: 'Dubai Airport',
    });
    assert.equal(details.dropoff, null);
});

test('resolveOfferMapDetails returns both points when dropoff differs', () => {
    const details = resolveOfferMapDetails(
        { name: 'Dubai Airport', latitude: 25.250291, longitude: 55.345171 },
        { name: 'Abu Dhabi Airport', latitude: 24.433, longitude: 54.651 },
    );

    assert.equal(details.hasMap, true);
    assert.equal(details.hasPickup, true);
    assert.equal(details.hasDropoff, true);
    assert.equal(details.pickup?.name, 'Dubai Airport');
    assert.equal(details.dropoff?.name, 'Abu Dhabi Airport');
});
