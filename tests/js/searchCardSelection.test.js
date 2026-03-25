import test from 'node:test';
import assert from 'node:assert/strict';

import { buildSearchCardSelection } from '../../resources/js/features/search/utils/searchCardSelection.js';

test('search card selection returns BAS for generic single-product providers', () => {
    assert.deepEqual(buildSearchCardSelection({
        vehicle: { source: 'renteon', id: 'v1' },
    }), {
        vehicle: { source: 'renteon', id: 'v1' },
        package: 'BAS',
    });
});

test('search card selection returns the chosen GM package', () => {
    assert.deepEqual(buildSearchCardSelection({
        vehicle: { source: 'greenmotion', id: 'v2' },
        selectedPackage: 'PRE',
    }), {
        vehicle: { source: 'greenmotion', id: 'v2' },
        package: 'PRE',
    });
});

test('search card selection maps locauto protection choices', () => {
    assert.deepEqual(buildSearchCardSelection({
        vehicle: { source: 'locauto_rent', id: 'v3' },
        selectedProtection: { code: '147', amount: 12.5 },
    }), {
        vehicle: { source: 'locauto_rent', id: 'v3' },
        package: 'POA',
        protection_code: '147',
        protection_amount: 12.5,
    });
});

test('search card selection maps adobe protection choices', () => {
    assert.deepEqual(buildSearchCardSelection({
        vehicle: { source: 'adobe', id: 'v4' },
        selectedProtection: { code: 'LDW', amount: 30 },
        baseTotal: 100,
    }), {
        vehicle: { source: 'adobe', id: 'v4' },
        package: 'LDW',
        protection_code: 'LDW',
        protection_amount: 30,
        total_price: 130,
    });
});

test('search card selection maps internal vendor plans', () => {
    assert.deepEqual(buildSearchCardSelection({
        vehicle: { source: 'internal', id: 'v5' },
        selectedInternalPlan: { id: 9, plan_type: 'PRE', price: 18 },
    }), {
        vehicle: { source: 'internal', id: 'v5' },
        package: 'PRE',
        protection_code: '9',
        protection_amount: 18,
        vendor_plan_id: 9,
    });
});
