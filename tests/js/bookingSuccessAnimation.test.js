import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import path from 'node:path';

const repoRoot = path.resolve(process.cwd());
const read = (relativePath) => readFileSync(path.join(repoRoot, relativePath), 'utf8');

test('booking success page uses an enlarged lottie container for the confirmation tick', () => {
    const successPage = read('resources/js/Pages/Booking/Success.vue');

    assert.match(successPage, /const successAnimationData = \(\(\) => \{/);
    assert.match(successPage, /filter\(\(layer\) => layer\?\.nm !== 'Payment Successful'\)/);
    assert.match(successPage, /cloned\.w = 960/);
    assert.match(successPage, /cloned\.h = 720/);
    assert.match(successPage, /class="mb-4 flex justify-center success-lottie-wrap"/);
    assert.match(successPage, /class="success-lottie"/);
    assert.match(successPage, /:animation-data="successAnimationData"/);
    assert.match(successPage, /:height="132"/);
    assert.match(successPage, /:width="132"/);
    assert.match(successPage, /:scale="1\.2"/);
    assert.match(successPage, /:no-margin="true"/);
    assert.match(successPage, /\.success-lottie-wrap/);
    assert.doesNotMatch(successPage, /:deep\(svg\)/);
});
