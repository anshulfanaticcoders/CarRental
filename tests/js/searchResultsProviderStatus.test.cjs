const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const projectRoot = '/mnt/c/laragon/www/CarRental';

const read = (relativePath) => fs.readFileSync(path.join(projectRoot, relativePath), 'utf8');

test('SearchResults renders a visible provider warning banner when some suppliers fail', () => {
    const source = read('resources/js/Pages/SearchResults.vue');

    assert.ok(source.includes("v-if=\"bookingStep === 'results' && hasProviderErrors\""));
    assert.ok(source.includes('Some providers could not return results for this search.'));
    assert.ok(source.includes('providerStatusErrors'));
    assert.ok(source.includes('formatProviderError'));
});
