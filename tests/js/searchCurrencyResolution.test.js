import test from 'node:test';
import assert from 'node:assert/strict';

import { resolveSearchCurrency } from '../../resources/js/utils/searchCurrency.js';

test('uses the selected site currency when the search has no explicit currency', () => {
    assert.equal(
        resolveSearchCurrency({
            currentCurrency: null,
            prefillCurrency: null,
            selectedCurrency: 'EUR',
        }),
        'EUR',
    );
});

test('preserves an explicit search currency when one is already set', () => {
    assert.equal(
        resolveSearchCurrency({
            currentCurrency: 'USD',
            prefillCurrency: null,
            selectedCurrency: 'EUR',
        }),
        'USD',
    );
});
