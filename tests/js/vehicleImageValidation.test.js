import assert from 'node:assert/strict';
import { after, beforeEach, test } from 'node:test';

import {
    VEHICLE_IMAGE_MAX_FILE_SIZE,
    validateVehicleImageFiles,
} from '../../resources/js/utils/vehicleImageValidation.js';

const originalImage = global.Image;
const originalCreateObjectURL = URL.createObjectURL;
const originalRevokeObjectURL = URL.revokeObjectURL;

let nextImage;

global.Image = class {
    set src(value) {
        this._src = value;
        queueMicrotask(() => {
            if (nextImage.error) {
                this.onerror?.();
                return;
            }

            this.naturalWidth = nextImage.width;
            this.naturalHeight = nextImage.height;
            this.onload?.();
        });
    }
};

URL.createObjectURL = () => 'blob:vehicle-image';
URL.revokeObjectURL = () => {};

beforeEach(() => {
    nextImage = { width: 800, height: 1200, error: false };
});

after(() => {
    global.Image = originalImage;
    URL.createObjectURL = originalCreateObjectURL;
    URL.revokeObjectURL = originalRevokeObjectURL;
});

const imageFile = (overrides = {}) => ({
    name: 'portrait-car.jpg',
    type: 'image/jpeg',
    size: 120_000,
    ...overrides,
});

test('vehicle image validation accepts portrait images', async () => {
    const result = await validateVehicleImageFiles([imageFile()]);

    assert.equal(result, null);
});

test('vehicle image validation rejects unsupported image types', async () => {
    const result = await validateVehicleImageFiles([imageFile({ type: 'image/webp' })]);

    assert.equal(result, 'Please upload only JPG or PNG images.');
});

test('vehicle image validation rejects oversized files', async () => {
    const result = await validateVehicleImageFiles([
        imageFile({ size: VEHICLE_IMAGE_MAX_FILE_SIZE + 1 }),
    ]);

    assert.equal(result, 'Image size must be under 5MB.');
});

test('vehicle image validation rejects unreadable images', async () => {
    nextImage = { width: 0, height: 0, error: true };

    const result = await validateVehicleImageFiles([imageFile()]);

    assert.match(result, /could not read the dimensions/);
});
