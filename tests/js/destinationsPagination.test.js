import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import path from 'node:path';

const repoRoot = path.resolve(process.cwd());
const controllerPath = path.join(repoRoot, 'app/Http/Controllers/PopularPlacesController.php');
const pagePath = path.join(repoRoot, 'resources/js/Pages/Destinations/Index.vue');

test('destinations controller paginates popular places by 12 per page', () => {
    const source = readFileSync(controllerPath, 'utf8');

    assert.match(source, /->paginate\(12\)/);
    assert.match(source, /SchemaBuilder::popularPlaceList\(\$places->getCollection\(\)/);
});

test('destinations page renders paginated data and pagination controls', () => {
    const source = readFileSync(pagePath, 'utf8');

    assert.match(source, /popularPlaces:\s*\{\s*type:\s*Object/);
    assert.match(source, /props\.popularPlaces\?\.data\?\.length/);
    assert.match(source, /const\s+paginationLinks\s*=\s*computed/);
    assert.match(source, /v-if="paginationLinks\.length > 1"/);
    assert.match(source, /const\s+goToPage\s*=\s*\(url\)\s*=>/);
    assert.match(source, /router\.visit\(url,\s*\{/);
    assert.match(source, /window\.scrollTo\(\{\s*top:\s*0,\s*behavior:\s*'smooth'\s*\}\)/);
});

test('destinations page applies reveal animation classes and composable', () => {
    const source = readFileSync(pagePath, 'utf8');

    assert.match(source, /import\s+\{\s*useScrollAnimation\s*\}\s+from\s+['"]@\/composables\/useScrollAnimation['"]/);
    assert.match(source, /useScrollAnimation\('.destinations-grid-section',\s*'.destination-card, \.destinations-pagination'/);
    assert.match(source, /class=\"destination-card sr-reveal\"/);
    assert.match(source, /\.sr-reveal\s*\{\s*visibility:\s*hidden;\s*\}/);
});
