import test from 'node:test';
import assert from 'node:assert/strict';

import {
    buildSicilyByCarOptionalExtras,
    expandSicilyByCarSelectedExtras,
} from '../../resources/js/utils/sicilyByCarExtras.js';

test('groups numbered Sicily slot extras into one logical display extra', () => {
    const extras = buildSicilyByCarOptionalExtras([
        {
            id: 'AD1',
            description: 'Additional driver #1',
            total: 100.05,
            payment: 'PayOnArrival',
        },
        {
            id: 'AD2',
            description: 'Additional driver #2',
            total: 100.05,
            payment: 'PayOnArrival',
        },
        {
            id: 'CS1',
            description: 'Child seat #1',
            total: 48.12,
            payment: 'PayOnArrival',
        },
        {
            id: 'CS2',
            description: 'Child seat #2',
            total: 48.12,
            payment: 'PayOnArrival',
        },
        {
            id: 'CS3',
            description: 'Child seat #3',
            total: 48.12,
            payment: 'PayOnArrival',
        },
        {
            id: 'SNO',
            description: 'Snow chains',
            total: 34.97,
            payment: 'PayOnArrival',
        },
    ], 10);

    assert.equal(extras.length, 3);

    const additionalDriver = extras.find((extra) => extra.code === 'AD');
    assert.ok(additionalDriver);
    assert.equal(additionalDriver.name, 'Additional driver');
    assert.equal(additionalDriver.numberAllowed, 2);
    assert.equal(additionalDriver.service_slots.length, 2);

    const childSeat = extras.find((extra) => extra.code === 'CS');
    assert.ok(childSeat);
    assert.equal(childSeat.name, 'Child seat');
    assert.equal(childSeat.numberAllowed, 3);
    assert.equal(childSeat.service_slots.length, 3);

    const snowChains = extras.find((extra) => extra.code === 'SNO');
    assert.ok(snowChains);
    assert.equal(snowChains.name, 'Snow chains');
    assert.equal(snowChains.numberAllowed, 1);
});

test('expands grouped Sicily extras back to provider slot ids for checkout', () => {
    const extras = buildSicilyByCarOptionalExtras([
        {
            id: 'AD1',
            description: 'Additional driver #1',
            total: 100.05,
            payment: 'PayOnArrival',
        },
        {
            id: 'AD2',
            description: 'Additional driver #2',
            total: 100.05,
            payment: 'PayOnArrival',
        },
        {
            id: 'SNO',
            description: 'Snow chains',
            total: 34.97,
            payment: 'PayOnArrival',
        },
    ], 10);

    const expanded = expandSicilyByCarSelectedExtras(
        {
            sbc_group_AD: 2,
            sbc_extra_SNO_2: 1,
        },
        extras,
    );

    assert.deepEqual(
        expanded.map((extra) => ({ id: extra.id, name: extra.name, qty: extra.qty, total: extra.total })),
        [
            { id: 'AD1', name: 'Additional driver', qty: 1, total: 100.05 },
            { id: 'AD2', name: 'Additional driver', qty: 1, total: 100.05 },
            { id: 'SNO', name: 'Snow chains', qty: 1, total: 34.97 },
        ],
    );
});
