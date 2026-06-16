import test from 'node:test';
import assert from 'node:assert/strict';

import { createAdobeAdapter } from '../../resources/js/Components/BookingExtras/adapters/adobeAdapter.js';

test('adobe adapter shows only source-backed included items', () => {
    const adapter = createAdobeAdapter({
        vehicle: {
            source: 'adobe',
            tdr: 200,
            pli: 54.24,
            ldw: 15.82,
            spp: 20.34,
            pricing: { total_price: 200 },
        },
        numberOfDays: 4,
    });

    assert.deepEqual(adapter.includedItems.value, [
        { label: 'Liability Protection (PLI)', detail: 'Mandatory - added in booking summary' },
    ]);
});
