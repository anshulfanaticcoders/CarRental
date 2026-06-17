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

test('recordgo adapter uses supplier complement ids for coverage protections', () => {
    const adapter = createRecordGoAdapter({
        vehicle: {
            source: 'recordgo',
            currency: 'EUR',
            total_price: 145.64,
            recordgo_products: [
                {
                    type: 'RG_PRE',
                    name: 'Just Go',
                    total: 145.64,
                    complements_included: [],
                    complements_automatic: [],
                    complements_associated: [
                        {
                            complementId: 44,
                            complementName: 'Full Cover',
                            complementCategory: 'COVERAGE',
                            priceTaxIncComplement: 18.5,
                        },
                    ],
                },
            ],
        },
        numberOfDays: 2,
    }, {
        currentPackage: ref('RG_PRE'),
        stripHtml,
    });

    assert.equal(adapter.protectionPlans.value[0].id, 'ext_recordgo_44');
    assert.equal(adapter.protectionPlans.value[0].code, 44);
});
