import test from 'node:test';
import assert from 'node:assert/strict';

import {
    buildVehiclePlaceholderDataUri,
    resolveVehicleImageSource,
} from '../../resources/js/utils/vehicleImageFallback.js';

test('resolveVehicleImageSource preserves explicit provider images', () => {
    const vehicle = {
        source: 'okmobility',
        brand: 'Fiat',
        model: '500',
        image: 'https://example.com/fiat-500.png',
    };

    assert.equal(resolveVehicleImageSource(vehicle), 'https://example.com/fiat-500.png');
});

test('resolveVehicleImageSource generates stable distinct fallbacks for vehicles missing images', () => {
    const fordFocus = {
        source: 'renteon',
        brand: 'Ford',
        model: 'Focus',
        category: 'compact',
        transmission: 'manual',
    };
    const hyundaiKona = {
        source: 'renteon',
        brand: 'Hyundai',
        model: 'Kona',
        category: 'suv',
        transmission: 'automatic',
    };

    const fordImage = resolveVehicleImageSource(fordFocus);
    const konaImage = resolveVehicleImageSource(hyundaiKona);

    assert.match(fordImage, /^data:image\/svg\+xml;charset=UTF-8,/);
    assert.match(konaImage, /^data:image\/svg\+xml;charset=UTF-8,/);
    assert.notEqual(fordImage, konaImage);
    assert.equal(fordImage, resolveVehicleImageSource(fordFocus));
});

test('resolveVehicleImageSource still prefers internal primary images', () => {
    const vehicle = {
        source: 'internal',
        brand: 'Toyota',
        model: 'Yaris',
        images: [
            { image_type: 'gallery', image_url: 'https://example.com/yaris-gallery.png' },
            { image_type: 'primary', image_url: 'https://example.com/yaris-primary.png' },
        ],
    };

    assert.equal(resolveVehicleImageSource(vehicle), 'https://example.com/yaris-primary.png');
});

test('buildVehiclePlaceholderDataUri includes vehicle-specific text context', () => {
    const uri = buildVehiclePlaceholderDataUri({
        source: 'renteon',
        brand: 'Ford',
        model: 'Focus',
        category: 'compact',
        transmission: 'manual',
    });

    const decoded = decodeURIComponent(uri.split(',', 2)[1]);

    assert.match(decoded, /Ford Focus/);
    assert.match(decoded, /COMPACT/);
    assert.match(decoded, /manual/i);
});
