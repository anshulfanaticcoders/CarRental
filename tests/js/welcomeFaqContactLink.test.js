import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import path from 'node:path';

const repoRoot = path.resolve(process.cwd());
const componentPath = path.join(repoRoot, 'resources/js/Components/Welcome/FaqSection.vue');

test('faq contact support link uses the active locale path instead of a root-level contact url', () => {
    const source = readFileSync(componentPath, 'utf8');

    assert.match(
        source,
        /const\s+contactSupportHref\s*=\s*computed\(\(\)\s*=>\s*`\/\$\{page\.props\.locale\s*\|\|\s*'en'\}\/contact-us`\s*\)/
    );
    assert.match(source, /<Link\s+:href="contactSupportHref"\s+class="faq-btn">Contact Support<\/Link>/);
    assert.equal(/href="\/contact-us"/.test(source), false);
});
