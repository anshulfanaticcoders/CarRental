const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const projectRoot = '/mnt/c/laragon/www/CarRental';

const read = (relativePath) => fs.readFileSync(path.join(projectRoot, relativePath), 'utf8');

test('SearchResults no longer carries standalone provider detail route mapping for unified providers', () => {
    const source = read('resources/js/Pages/SearchResults.vue');

    assert.ok(!source.includes('ok-mobility-car.show'));
    assert.ok(!source.includes('adobe-car.show'));
    assert.ok(!source.includes('locauto-rent-car.show'));
    assert.ok(!source.includes('renteon-car.show'));
    assert.ok(!source.includes('const getProviderRoute ='));
    assert.ok(!source.includes('const detailRoute ='));
});

test('CarListingCard does not route any provider through standalone detail pages', () => {
    const source = read('resources/js/Components/CarListingCard.vue');

    assert.ok(!source.includes('wheelsys-car.show'));
    assert.ok(!source.includes('ok-mobility-car.show'));
    assert.ok(!source.includes('adobe-car.show'));
    assert.ok(!source.includes('locauto-rent-car.show'));
    assert.ok(!source.includes('green-motion-car.show'));
});

test('Legacy provider-specific Green Motion and Wheelsys pages are removed from the unified frontend flow', () => {
    const searchResults = read('resources/js/Pages/SearchResults.vue');

    assert.ok(!searchResults.includes('green-motion-cars'));
    assert.ok(!searchResults.includes('green-motion-car.show'));
    assert.ok(!searchResults.includes('green-motion-locations'));
    assert.ok(!searchResults.includes('green-motion-countries'));
    assert.ok(!searchResults.includes('green-motion-terms-and-conditions'));
    assert.ok(!searchResults.includes('wheelsys-car.show'));
});

test('SearchResults uses the unified vehicles paginator as the single source of truth', () => {
    const source = read('resources/js/Pages/SearchResults.vue');

    assert.ok(!source.includes('props.okMobilityVehicles?.data'));
    assert.ok(!source.includes('props.renteonVehicles?.data'));
    assert.ok(source.includes('const base = props.vehicles?.data || [];'));
});

test('Backend search payload no longer exposes provider-specific side channels', () => {
    const controller = read('app/Http/Controllers/SearchController.php');
    const gatewaySearch = read('app/Services/Search/GatewaySearchService.php');

    assert.ok(!controller.includes("'okMobilityVehicles'"));
    assert.ok(!controller.includes("'renteonVehicles'"));
    assert.ok(!gatewaySearch.includes("'okMobilityVehicles'"));
    assert.ok(!gatewaySearch.includes("'renteonVehicles'"));
});
