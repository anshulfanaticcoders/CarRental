import test from 'node:test';
import assert from 'node:assert/strict';

import { createBookingLocationDisplay } from '../../resources/js/Components/BookingExtras/composables/useBookingLocationDisplay.js';

test('one-way return label wins when provider dropoff repeats pickup details', () => {
    const display = createBookingLocationDisplay({
        pickupLocation: 'Dubai Airport (DXB)',
        dropoffLocation: 'Dubai Downtown',
        sameLocation: false,
        vehicle: {
            dropoff_address: '34 24 St - Hor Al Anz East - Dubai - United Arab Emirates',
        },
        locationDetails: {
            name: 'Dubai Airport',
            address_1: '34 24 St - Hor Al Anz East - Dubai - United Arab Emirates',
        },
        dropoffLocationDetails: {
            name: 'Dubai Airport',
            address_1: '34 24 St - Hor Al Anz East - Dubai - United Arab Emirates',
        },
        adapterLocationData: {
            pickupStation: 'Dubai Airport',
            pickupAddress: '34 24 St - Hor Al Anz East - Dubai - United Arab Emirates',
            dropoffStation: 'Dubai Airport',
            dropoffAddress: '34 24 St - Hor Al Anz East - Dubai - United Arab Emirates',
        },
    });

    assert.equal(display.dropoff.label, 'Dubai Downtown');
    assert.deepEqual(display.dropoff.addressLines, []);
    assert.equal(display.dropoff.parkingAddress, null);
    assert.equal(display.dropoff.isSameAsPickup, false);
});

test('distinct one-way return details remain visible', () => {
    const display = createBookingLocationDisplay({
        pickupLocation: 'Dubai Airport (DXB)',
        dropoffLocation: 'Dubai Downtown',
        sameLocation: false,
        locationDetails: {
            name: 'Dubai Airport',
            address_1: 'Dubai Airport Terminal 1',
        },
        dropoffLocationDetails: {
            name: 'Dubai Downtown',
            address_1: 'Business Bay, Dubai',
        },
    });

    assert.equal(display.dropoff.label, 'Dubai Downtown');
    assert.deepEqual(display.dropoff.addressLines, ['Business Bay, Dubai']);
    assert.equal(display.dropoff.isSameAsPickup, false);
});

test('round trip duplicate return details collapse into same pickup return', () => {
    const display = createBookingLocationDisplay({
        pickupLocation: 'Dubai Airport (DXB)',
        dropoffLocation: 'Dubai Airport (DXB)',
        sameLocation: true,
        locationDetails: {
            name: 'Dubai Airport',
            address_1: '34 24 St - Hor Al Anz East - Dubai - United Arab Emirates',
        },
        dropoffLocationDetails: {
            name: 'Dubai Airport',
            address_1: '34 24 St - Hor Al Anz East - Dubai - United Arab Emirates',
        },
        adapterLocationData: {
            pickupStation: 'Dubai Airport',
            pickupAddress: '34 24 St - Hor Al Anz East - Dubai - United Arab Emirates',
            dropoffStation: 'Dubai Airport',
            dropoffAddress: '34 24 St - Hor Al Anz East - Dubai - United Arab Emirates',
        },
    });

    assert.equal(display.dropoff.isSameAsPickup, true);
});
