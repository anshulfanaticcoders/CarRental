import test from 'node:test';
import assert from 'node:assert/strict';
import { ref } from 'vue';

import { createRecordGoAdapter } from '../../resources/js/Components/BookingExtras/adapters/recordGoAdapter.js';

const stripHtml = (value) => String(value || '').replace(/<[^>]*>/g, '');

test('recordgo adapter hides non-applied automatic complements from customer UI', () => {
    const adapter = createRecordGoAdapter({
        vehicle: {
            source: 'recordgo',
            currency: 'EUR',
            total_price: 145.64,
            recordgo_products: [
                {
                    type: 'RG_GO_EASY',
                    name: 'Go Easy',
                    total: 145.64,
                    complements_included: [
                        {
                            complementId: 41,
                            complementName: 'Basic Cover',
                            complementCategory: 'COVERAGE',
                        },
                    ],
                    complements_automatic: [
                        {
                            complementId: 67,
                            complementName: 'Young driver',
                            complementCategory: 'SERVICE',
                            priceTaxIncDay: 39.8,
                        },
                    ],
                    complements_associated: [],
                },
            ],
        },
        numberOfDays: 4,
    }, {
        currentPackage: ref('RG_GO_EASY'),
        stripHtml,
    });

    assert.deepEqual(adapter.recordGoAutomaticComplements.value, []);
    assert.deepEqual(adapter.includedItems.value, [
        { label: 'Basic Cover', detail: 'Included' },
    ]);
});
