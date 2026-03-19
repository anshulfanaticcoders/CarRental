import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import path from 'node:path';

const repoRoot = path.resolve(process.cwd());
const componentPath = path.join(repoRoot, 'resources/js/Components/Footer.vue');

test('footer newsletter feedback messages are rendered directly under the subscribe form', () => {
    const source = readFileSync(componentPath, 'utf8');

    assert.match(source, /<div class="footer-nl-form-wrap">/);
    assert.match(
        source,
        /<div class="footer-nl-form-wrap">[\s\S]*<form class="footer-nl-form"[\s\S]*<\/form>[\s\S]*<p v-if="newsletterError" class="footer-nl-hint is-error">\{\{ newsletterError \}\}<\/p>[\s\S]*<p v-if="newsletterSuccess" class="footer-nl-hint is-success">\{\{ newsletterSuccess \}\}<\/p>[\s\S]*<\/div>/
    );
});
