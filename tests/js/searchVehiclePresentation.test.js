import test from 'node:test';
import assert from 'node:assert/strict';

import {
    resolveSearchVehicleFeatureFlags,
    resolveSearchVehicleDisplayName,
    resolveSearchVehicleImage,
    resolveSearchVehicleSpecs,
} from '../../resources/js/features/search/utils/searchVehiclePresentation.js';

test('search vehicle presentation prefers canonical display fields over drifting legacy fields', () => {
    const vehicle = {
        source: 'internal',
        display_name: 'Toyota Yaris',
        brand: 'Toyota',
        model: 'Wrong Legacy Model',
        image: 'https://example.com/internal/yaris-primary.jpg',
        specs: {
            transmission: 'automatic',
            fuel: 'petrol',
            seating_capacity: 5,
            doors: 4,
            luggage_small: 1,
            luggage_medium: 1,
            luggage_large: 0,
            air_conditioning: true,
            sipp_code: null,
        },
        seating_capacity: 7,
        doors: 2,
        transmission: 'manual',
        fuel: 'diesel',
        airConditioning: false,
        booking_context: {
            provider_payload: {
                images: [
                    { image_type: 'primary', image_url: 'https://example.com/legacy/incorrect.jpg' },
                ],
            },
        },
    };

    assert.equal(resolveSearchVehicleDisplayName(vehicle), 'Toyota Yaris');
    assert.equal(resolveSearchVehicleImage(vehicle), 'https://example.com/internal/yaris-primary.jpg');
    assert.deepEqual(resolveSearchVehicleSpecs(vehicle), {
        passengers: 5,
        doors: 4,
        transmission: 'automatic',
        fuel: 'petrol',
        bagDisplay: 'S:1 M:1 L:0',
        acriss: null,
        airConditioning: true,
    });
});

test('search vehicle presentation keeps unknown specs missing instead of inventing values', () => {
    const vehicle = {
        source: 'xdrive',
        display_name: 'Hyundai i20',
        image: 'https://example.com/i20.jpg',
        specs: {
            transmission: null,
            fuel: null,
            seating_capacity: null,
            doors: null,
            luggage_small: null,
            luggage_medium: null,
            luggage_large: null,
            air_conditioning: null,
            sipp_code: null,
        },
        booking_context: {
            provider_payload: {},
        },
    };

    assert.deepEqual(resolveSearchVehicleSpecs(vehicle), {
        passengers: null,
        doors: null,
        transmission: null,
        fuel: null,
        bagDisplay: null,
        acriss: null,
        airConditioning: null,
    });
});

test('search vehicle feature flags use canonical cancellation and mileage data', () => {
    const vehicle = {
        mileage: 'unlimited',
        cancellation: {
            available: true,
        },
        benefits: {
            limited_km_per_day: true,
            cancellation_available_per_day: false,
        },
        source: 'wheelsys',
    };

    assert.deepEqual(resolveSearchVehicleFeatureFlags(vehicle), {
        freeCancellation: true,
        unlimitedMileage: true,
    });
});

test('search vehicle presentation does not fall back to drifting legacy title and image fields', () => {
    const vehicle = {
        source: 'renteon',
        brand: null,
        model: null,
        display_name: null,
        image: null,
        group_description: 'Wrong Legacy Group',
        image_url: 'https://example.com/wrong-legacy-image.jpg',
        booking_context: {
            provider_payload: {
                images: [
                    { image_type: 'primary', image_url: 'https://example.com/wrong-provider-payload.jpg' },
                ],
            },
        },
    };

    assert.equal(resolveSearchVehicleDisplayName(vehicle), '');
    assert.equal(resolveSearchVehicleImage(vehicle), null);
});

test('search vehicle specs ignore dead fallback fields — passengers/adults/luggage_capacity/legacyPayload spec fields', () => {
    // This test documents the removed fallback paths.
    // The transformer never sets these fields on the search vehicle shape,
    // so they must not influence output even when present on the raw object.
    const vehicle = {
        source: 'xdrive',
        // Dead fallback fields that were previously in the chain
        passengers: 9,
        adults: 8,
        luggage_capacity: 5,
        luggage: 3,
        booking_context: {
            provider_payload: {
                seating_capacity: 99,
                doors: 77,
                transmission: 'wrong',
                fuel: 'wrong',
                luggage: 7,
                luggage_capacity: 6,
            },
        },
        // Canonical path is absent — result must be null for each field
        specs: {
            seating_capacity: null,
            doors: null,
            transmission: null,
            fuel: null,
        },
    };

    const result = resolveSearchVehicleSpecs(vehicle);
    assert.equal(result.passengers, null, 'passengers must not fall back to vehicle.passengers or vehicle.adults');
    assert.equal(result.doors, null, 'doors must not fall back to legacyPayload.doors');
    assert.equal(result.transmission, null, 'transmission must not fall back to legacyPayload.transmission');
    assert.equal(result.fuel, null, 'fuel must not fall back to legacyPayload.fuel');
    assert.equal(result.bagDisplay, null, 'bagDisplay must not fall back to vehicle.luggage or vehicle.luggage_capacity');
});
