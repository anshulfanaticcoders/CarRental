import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import path from 'node:path';

const repoRoot = path.resolve(process.cwd());
const componentPath = path.join(repoRoot, 'resources/js/Components/Welcome/TopDestinations.vue');

test('top destinations uses carousel UI and caps homepage items to 10', () => {
    const source = readFileSync(componentPath, 'utf8');

    assert.match(source, /Carousel,\s*CarouselContent,\s*CarouselItem,\s*CarouselNext,\s*CarouselPrevious/);
    assert.match(source, /@init-api="onInitApi"/);
    assert.match(source, /props\.popularPlaces\?\.length\s*\?\s*props\.popularPlaces\.slice\(0,\s*10\)/);
    assert.match(source, /const\s+showCarouselControls\s*=\s*computed\(\(\)\s*=>\s*places\.value\.length\s*>\s*5\)/);
    assert.match(source, /:opts="\{\s*align:\s*'start',\s*loop:\s*showCarouselControls\s*\}"/);
});

test('top destinations includes pagination dots for carousel navigation', () => {
    const source = readFileSync(componentPath, 'utf8');

    assert.match(source, /const\s+selectedIndex\s*=\s*ref\(0\)/);
    assert.match(source, /const\s+snapCount\s*=\s*ref\(0\)/);
    assert.match(source, /v-if="showCarouselControls"/);
    assert.match(source, /<CarouselPrevious[^>]*v-if="showCarouselControls"/);
    assert.match(source, /<CarouselNext[^>]*v-if="showCarouselControls"/);
    assert.match(source, /@click="scrollTo\(i\)"/);
});
