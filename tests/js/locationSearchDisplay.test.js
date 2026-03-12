import test from 'node:test';
import assert from 'node:assert/strict';

import {
    buildLocationInputValue,
    formatLocationPrimaryLabel,
    formatLocationSecondaryLabel,
    getLocationSelectionData,
} from '../../resources/js/utils/locationSearchDisplay.js';

const dubaiAirportDxb = {
    name: 'Dubai Airport',
    aliases: [
        'Dubai Airport Terminal 1',
        'Dubai',
        'Dubai Airport Terminal 2',
        'Dubai Airport Terminal 3',
        'Dubai International Airport (DXB)',
        'DXB',
    ],
    city: 'Dubai',
    country: 'United Arab Emirates',
    country_code: 'AE',
    location_type: 'airport',
    iata: 'DXB',
};

const dubaiAirportDwc = {
    name: 'Dubai Airport',
    aliases: ['Dubai Al Maktoum Airport', 'Dubai', 'DWC'],
    city: 'Dubai',
    country: 'United Arab Emirates',
    country_code: 'AE',
    location_type: 'airport',
    iata: 'DWC',
};

const dubaiPort = {
    name: 'Dubai Port',
    aliases: ['Dubai'],
    city: 'Dubai',
    country: '239',
    country_code: '',
    location_type: 'port',
    iata: null,
};

const dubaiDowntown = {
    name: 'Bur Downtown',
    aliases: ['Dubai Downtown', 'Bur'],
    city: 'Bur',
    country: 'United Arab Emirates',
    country_code: 'AE',
    location_type: 'downtown',
    iata: null,
};

test('disambiguates airport results with IATA codes', () => {
    assert.equal(formatLocationPrimaryLabel(dubaiAirportDxb), 'Dubai Airport (DXB)');
    assert.equal(formatLocationPrimaryLabel(dubaiAirportDwc), 'Dubai Airport (DWC)');
});

test('suppresses numeric country values in the secondary label', () => {
    assert.equal(formatLocationSecondaryLabel(dubaiPort), 'Dubai');
});

test('prefers a better downtown alias and derives a clean city for selection', () => {
    assert.equal(formatLocationPrimaryLabel(dubaiDowntown), 'Dubai Downtown');
    assert.equal(formatLocationSecondaryLabel(dubaiDowntown), 'Dubai, United Arab Emirates');
    assert.deepEqual(getLocationSelectionData(dubaiDowntown), {
        displayName: 'Dubai Downtown',
        city: 'Dubai',
        country: 'United Arab Emirates',
    });
    assert.equal(buildLocationInputValue(dubaiDowntown), 'Dubai Downtown');
});
