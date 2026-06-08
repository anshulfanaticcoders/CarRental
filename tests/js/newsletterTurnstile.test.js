import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import path from 'node:path';

const repoRoot = path.resolve(process.cwd());
const newsletterComponents = [
    'resources/js/Components/Footer.vue',
    'resources/js/Components/NewsletterSection.vue',
    'resources/js/Components/Welcome/NewsletterSection.vue',
];

for (const component of newsletterComponents) {
    test(`${component} submits a Turnstile token with newsletter signups`, () => {
        const source = readFileSync(path.join(repoRoot, component), 'utf8');

        assert.match(source, /useTurnstile/);
        assert.match(source, /turnstileContainer/);
        assert.match(source, /turnstileToken/);
        assert.match(source, /cf_turnstile_response:\s*turnstileToken\.value/);
        assert.match(source, /resetTurnstile\(\)/);
    });
}
