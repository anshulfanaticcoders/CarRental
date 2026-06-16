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
        assert.match(source, /executeTurnstile/);
        assert.match(source, /appearance:\s*'interaction-only'/);
        assert.match(source, /execution:\s*'execute'/);
        assert.match(source, /cf_turnstile_response:\s*turnstileResponse/);
        assert.match(source, /resetTurnstile\(\)/);
    });
}

test('Turnstile composable supports official execute mode callbacks', () => {
    const source = readFileSync(path.join(repoRoot, 'resources/js/composables/useTurnstile.js'), 'utf8');

    assert.match(source, /renderOptions\.appearance\s*=\s*options\.appearance/);
    assert.match(source, /renderOptions\.execution\s*=\s*options\.execution/);
    assert.match(source, /executeTurnstile/);
    assert.match(source, /window\.turnstile\.execute/);
});
