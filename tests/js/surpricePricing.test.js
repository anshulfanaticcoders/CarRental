import test from 'node:test';
import assert from 'node:assert/strict';
import { reactive } from 'vue';

import { createSurpriceAdapter } from '../../resources/js/Components/BookingExtras/adapters/surpriceAdapter.js';

/**
 * Helper: build a minimal Surprice vehicle as it arrives from the
 * GatewayVehicleTransformer's canonical path.  The gateway does NOT
 * put entries in supplier_data.products for Surprice, so the
 * transformer's `products` array is empty by default.
 */
function makeSurpriceVehicle(overrides = {}) {
    return {
        source: 'surprice',
        total_price: 245.50,
        price_per_day: 49.10,
        currency: 'EUR',
        pricing: {
            total_price: 245.50,
            price_per_day: 49.10,
            currency: 'EUR',
        },
        products: [],
        extras: [],
        supplier_data: {},
        ...overrides,
    };
}

// ── packages fallback ──────────────────────────────────────────────

test('surprice adapter synthesises a BAS package when products array is empty', () => {
    const props = reactive({
        vehicle: makeSurpriceVehicle(),
        numberOfDays: 5,
    });

    const adapter = createSurpriceAdapter(props);
    const pkgs = adapter.packages.value;

    assert.equal(pkgs.length, 1, 'should have exactly one synthetic package');
    assert.equal(pkgs[0].type, 'BAS');
    assert.equal(pkgs[0].total, 245.50);
    assert.equal(pkgs[0].currency, 'EUR');
});

test('surprice adapter uses real products when products array is populated', () => {
    const props = reactive({
        vehicle: makeSurpriceVehicle({
            products: [
                { type: 'BAS', total: 200, currency: 'EUR' },
                { type: 'PLU', total: 260, currency: 'EUR' },
            ],
        }),
        numberOfDays: 5,
    });

    const adapter = createSurpriceAdapter(props);
    const pkgs = adapter.packages.value;

    assert.equal(pkgs.length, 2);
    assert.equal(pkgs[0].type, 'BAS');
    assert.equal(pkgs[0].total, 200);
    assert.equal(pkgs[1].type, 'PLU');
    assert.equal(pkgs[1].total, 260);
});

// ── computeNetTotal ────────────────────────────────────────────────

test('surprice computeNetTotal returns package total + extras when currentProduct exists', () => {
    const props = reactive({
        vehicle: makeSurpriceVehicle(),
        numberOfDays: 5,
    });

    const adapter = createSurpriceAdapter(props);
    const currentProduct = { type: 'BAS', total: 245.50, currency: 'EUR' };
    const result = adapter.computeNetTotal(30, currentProduct);

    assert.equal(result, 275.50);
});

test('surprice computeNetTotal falls back to vehicle total_price when currentProduct is null', () => {
    const props = reactive({
        vehicle: makeSurpriceVehicle(),
        numberOfDays: 5,
    });

    const adapter = createSurpriceAdapter(props);
    const result = adapter.computeNetTotal(0, null);

    assert.equal(result, 245.50);
});

test('surprice computeNetTotal falls back to vehicle total_price when currentProduct has no total', () => {
    const props = reactive({
        vehicle: makeSurpriceVehicle(),
        numberOfDays: 5,
    });

    const adapter = createSurpriceAdapter(props);
    const result = adapter.computeNetTotal(10, {});

    assert.equal(result, 255.50);
});

// ── baseTotal ──────────────────────────────────────────────────────

test('surprice baseTotal uses synthetic BAS package total when products are empty', () => {
    const props = reactive({
        vehicle: makeSurpriceVehicle(),
        numberOfDays: 5,
    });

    const adapter = createSurpriceAdapter(props);

    assert.equal(adapter.baseTotal.value, 245.50);
});

test('surprice baseTotal uses BAS product total when products are populated', () => {
    const props = reactive({
        vehicle: makeSurpriceVehicle({
            products: [
                { type: 'BAS', total: 200, currency: 'EUR' },
            ],
        }),
        numberOfDays: 4,
    });

    const adapter = createSurpriceAdapter(props);

    assert.equal(adapter.baseTotal.value, 200);
});

// ── End-to-end: $0.00 scenario ─────────────────────────────────────

test('surprice pricing never returns $0.00 when vehicle has valid total_price', () => {
    const props = reactive({
        vehicle: makeSurpriceVehicle({ products: [] }),
        numberOfDays: 5,
    });

    const adapter = createSurpriceAdapter(props);

    // Simulate the BookingExtrasStep flow: currentProduct comes from
    // adapter.packages, which should now have a synthetic BAS package
    const currentProduct = adapter.packages.value[0] || null;

    const netTotal = adapter.computeNetTotal(0, currentProduct);

    assert.ok(netTotal > 0, `netTotal should be > 0 but got ${netTotal}`);
    assert.equal(netTotal, 245.50);
});
