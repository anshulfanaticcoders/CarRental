import test from 'node:test';
import assert from 'node:assert/strict';

import {
    buildPopularPlaceSearchUrl,
    resolveUnifiedLocationForPopularPlace,
} from '../../resources/js/utils/popularPlaceSearch.js';

test('buildPopularPlaceSearchUrl includes required unified IDs for gateway search', () => {
    const place = {
        place_name: 'Dubai',
        city: 'Dubai',
        country: 'United Arab Emirates',
        latitude: 25.2048,
        longitude: 55.2708,
    };

    const unifiedLocations = [
        {
            unified_location_id: 3272373056,
            name: 'Dubai Airport',
            city: 'Dubai',
            country: 'United Arab Emirates',
            latitude: 25.24,
            longitude: 55.35,
            providers: [
                { provider: 'okmobility', pickup_id: '641' },
            ],
        },
    ];

    const url = buildPopularPlaceSearchUrl(place, unifiedLocations, {
        now: new Date('2026-03-18T00:00:00Z'),
    });

    assert.ok(url?.startsWith('/s?'));

    const query = new URLSearchParams(url.replace('/s?', ''));
    assert.equal(query.get('unified_location_id'), '3272373056');
    assert.equal(query.get('dropoff_unified_location_id'), '3272373056');
    assert.equal(query.get('provider'), 'mixed');
    assert.equal(query.get('where'), 'Dubai Airport');
    assert.equal(query.get('date_from'), '2026-03-19');
    assert.equal(query.get('date_to'), '2026-03-20');
});

test('resolveUnifiedLocationForPopularPlace uses stored unified_location_id first when available', () => {
    const place = {
        place_name: 'Barcelona',
        city: 'Barcelona',
        country: 'Spain',
        unified_location_id: 4247078518,
    };

    const unifiedLocations = [
        {
            unified_location_id: 4247078518,
            name: 'Mallorca Airport',
            city: 'Palma',
            country: 'Spain',
            latitude: 39.55,
            longitude: 2.73,
            providers: [{ provider: 'okmobility', pickup_id: '01' }],
        },
    ];

    const resolved = resolveUnifiedLocationForPopularPlace(place, unifiedLocations);
    assert.equal(resolved?.unified_location_id, 4247078518);
});

