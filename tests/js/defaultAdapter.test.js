import test from 'node:test';
import assert from 'node:assert/strict';

import { createDefaultAdapter } from '../../resources/js/Components/BookingExtras/adapters/defaultAdapter.js';

test('default adapter maps arbitrary provider products into booking packages', () => {
    const adapter = createDefaultAdapter({
        vehicle: {
            total_price: 129.28,
            price_per_day: 43.09,
            currency: 'EUR',
            products: [
                { type: 'POA', name: 'Pay at Pick-up', total: 129.28, price_per_day: 43.09, currency: 'EUR' },
                { type: 'PAY_NOW', name: 'Pay Now', total: 116.09, price_per_day: 38.70, currency: 'EUR' },
            ],
        },
        optionalExtras: [],
        numberOfDays: 3,
    });

    assert.deepEqual(adapter.packages.value, [
        {
            type: 'POA',
            name: 'Pay at Pick-up',
            subtitle: 'Rental option',
            pricePerDay: 43.09,
            total: 129.28,
            deposit: null,
            excess: null,
            deposit_currency: null,
            excess_currency: null,
            excessTheft: null,
            benefits: [],
            isBestValue: true,
            isAddOn: false,
            currency: 'EUR',
        },
        {
            type: 'PAY_NOW',
            name: 'Pay Now',
            subtitle: 'Rental option',
            pricePerDay: 38.7,
            total: 116.09,
            deposit: null,
            excess: null,
            deposit_currency: null,
            excess_currency: null,
            excessTheft: null,
            benefits: [],
            isBestValue: false,
            isAddOn: false,
            currency: 'EUR',
        },
    ]);
});

test('default adapter exposes included insurance and generic extras from gateway vehicles', () => {
    const adapter = createDefaultAdapter({
        vehicle: {
            pricing: {
                deposit_currency: 'USD',
            },
            benefits: {
                deposit_currency: 'USD',
                excess_amount: 500,
            },
            insurance_options: [
                {
                    id: 'ins_cdw_basic',
                    name: 'CDW',
                    coverage_type: 'basic',
                    included: true,
                    total_price: 0,
                    excess_amount: 500,
                    description: 'Supplier terms require proof of coverage unless local coverage is purchased.',
                },
                {
                    id: 'ins_premium',
                    name: 'Premium Cover',
                    coverage_type: 'premium',
                    included: false,
                    daily_rate: 6,
                    total_price: 18,
                    currency: 'EUR',
                },
            ],
            extras: [
                {
                    id: 'gps',
                    code: 'GPS',
                    name: 'GPS Navigation',
                    total_for_booking: 15,
                    daily_rate: 5,
                    currency: 'EUR',
                },
            ],
        },
        optionalExtras: [],
        numberOfDays: 3,
    });

    assert.deepEqual(adapter.includedItems.value, [
        {
            label: 'CDW',
            detail: 'Supplier terms require proof of coverage unless local coverage is purchased.',
        },
    ]);
    assert.equal(adapter.protectionPlans.value.length, 1);
    assert.equal(adapter.protectionPlans.value[0].name, 'Premium Cover');
    assert.equal(adapter.protectionPlans.value[0].total_for_booking, 18);

    assert.deepEqual(adapter.optionalExtras.value, [
        {
            id: 'gps',
            code: 'GPS',
            name: 'GPS Navigation',
            description: 'GPS Navigation',
            price: 15,
            daily_rate: 5,
            total_for_booking: 15,
            amount: 15,
            currency: 'EUR',
            numberAllowed: 1,
            maxQuantity: 1,
            required: false,
            type: 'optional',
        },
    ]);
});

test('default adapter preserves supplier deposit and excess currency for generic products', () => {
    const adapter = createDefaultAdapter({
        vehicle: {
            currency: 'EUR',
            pricing: {
                deposit_currency: 'USD',
            },
            benefits: {
                deposit_currency: 'USD',
            },
            products: [
                {
                    type: 'POA',
                    name: 'Pay at Pick-up',
                    total: 105.47,
                    price_per_day: 26.37,
                    currency: 'EUR',
                    deposit: 500,
                    excess: 500,
                },
            ],
        },
        optionalExtras: [],
        numberOfDays: 4,
    });

    assert.equal(adapter.packages.value[0].deposit_currency, 'USD');
    assert.equal(adapter.packages.value[0].excess_currency, 'USD');
});

test('default adapter falls back to supplier excess when included insurance has no description', () => {
    const adapter = createDefaultAdapter({
        vehicle: {
            pricing: {
                deposit_currency: 'USD',
            },
            benefits: {
                deposit_currency: 'USD',
                excess_amount: 500,
            },
            insurance_options: [
                {
                    id: 'ins_cdw_basic',
                    name: 'CDW',
                    coverage_type: 'basic',
                    included: true,
                    total_price: 0,
                },
            ],
        },
        optionalExtras: [],
        numberOfDays: 3,
    });

    assert.deepEqual(adapter.includedItems.value, [
        {
            label: 'CDW',
            detail: 'Supplier terms apply; excess USD 500.00',
        },
    ]);
});

test('default adapter exposes generic provider location details when present', () => {
    const adapter = createDefaultAdapter({
        vehicle: {
            supplier_data: {
                pickup_instructions: 'Take the Easirent shuttle from bays A11-A13.',
                dropoff_instructions: 'Return to 1777 McCoy Road, Orlando, Florida 32809.',
            },
            location_details: {
                name: 'Orlando Airport',
                address_1: '1777 McCoy Road, Orlando, Florida 32809',
                telephone: '+1 555 123 4567',
                opening_hours: ['08:00', '22:00'],
                collection_details: 'Take the Easirent shuttle from bays A11-A13.',
                dropoff_instructions: 'Return to 1777 McCoy Road, Orlando, Florida 32809.',
            },
            pickup_station_name: 'Orlando Airport',
            pickup_address: '1777 McCoy Road, Orlando, Florida 32809',
            office_phone: '+1 555 123 4567',
        },
        optionalExtras: [],
        numberOfDays: 3,
    });

    assert.deepEqual(adapter.locationData.value, {
        pickupStation: 'Orlando Airport',
        pickupAddress: '1777 McCoy Road, Orlando, Florida 32809',
        pickupLines: [],
        pickupPhone: '+1 555 123 4567',
        pickupEmail: null,
        dropoffStation: null,
        dropoffAddress: null,
        dropoffLines: [],
        dropoffPhone: null,
        dropoffEmail: null,
        sameLocation: true,
        fuelPolicy: null,
        cancellation: null,
        officeHours: ['08:00', '22:00'],
        pickupInstructions: 'Take the Easirent shuttle from bays A11-A13.',
        dropoffInstructions: 'Return to 1777 McCoy Road, Orlando, Florida 32809.',
    });
});

test('default adapter separates insurance extras from ordinary add-ons', () => {
    const adapter = createDefaultAdapter({
        vehicle: {
            currency: 'EUR',
            extras: [
                {
                    id: 'ext_favrica_LCF',
                    code: 'LCF',
                    name: 'Loss & Damage Coverage (LCF)',
                    type: 'insurance',
                    total_for_booking: 14.97,
                    daily_rate: 3.74,
                    currency: 'EUR',
                },
                {
                    id: 'ext_favrica_SCDW',
                    code: 'SCDW',
                    name: 'Super Collision Damage Waiver (SCDW)',
                    type: 'insurance',
                    total_for_booking: 18.71,
                    daily_rate: 4.68,
                    currency: 'EUR',
                },
                {
                    id: 'ext_favrica_Baby_Seat',
                    code: 'Seat',
                    name: 'Baby Seat',
                    type: 'equipment',
                    total_for_booking: 11.23,
                    daily_rate: 2.81,
                    currency: 'EUR',
                },
            ],
        },
        optionalExtras: [],
        numberOfDays: 4,
    });

    assert.deepEqual(adapter.protectionPlans.value.map((item) => item.name), [
        'Loss & Damage Coverage (LCF)',
        'Super Collision Damage Waiver (SCDW)',
    ]);
    assert.deepEqual(adapter.optionalExtras.value.map((item) => item.name), ['Baby Seat']);
});
