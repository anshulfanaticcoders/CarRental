import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import path from 'node:path';

const repoRoot = path.resolve(process.cwd());
const topDestinationsPath = path.join(repoRoot, 'resources/js/Components/Welcome/TopDestinations.vue');
const destinationsPagePath = path.join(repoRoot, 'resources/js/Pages/Destinations/Index.vue');
const footerPath = path.join(repoRoot, 'resources/js/Components/Footer.vue');

test('popular place UI uses backend-provided search URLs instead of loading unified_locations.json', () => {
    const topDestinationsSource = readFileSync(topDestinationsPath, 'utf8');
    const destinationsPageSource = readFileSync(destinationsPagePath, 'utf8');
    const footerSource = readFileSync(footerPath, 'utf8');

    assert.doesNotMatch(topDestinationsSource, /unified_locations\.json/);
    assert.doesNotMatch(destinationsPageSource, /unified_locations\.json/);
    assert.doesNotMatch(footerSource, /unified_locations\.json/);

    assert.doesNotMatch(topDestinationsSource, /buildPopularPlaceSearchUrl/);
    assert.doesNotMatch(destinationsPageSource, /buildPopularPlaceSearchUrl/);

    assert.match(topDestinationsSource, /search_url/);
    assert.match(destinationsPageSource, /search_url/);
    assert.match(footerSource, /search_url/);
});

test('popular place UI no longer keeps a client-side unifiedLocations cache', () => {
    const topDestinationsSource = readFileSync(topDestinationsPath, 'utf8');
    const destinationsPageSource = readFileSync(destinationsPagePath, 'utf8');
    const footerSource = readFileSync(footerPath, 'utf8');

    assert.doesNotMatch(topDestinationsSource, /const\s+unifiedLocations\s*=\s*ref/);
    assert.doesNotMatch(destinationsPageSource, /const\s+unifiedLocations\s*=\s*ref/);
    assert.doesNotMatch(footerSource, /const\s+unifiedLocations\s*=\s*ref/);
});
