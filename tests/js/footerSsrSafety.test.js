import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

test('footer does not fetch relative API URLs during SSR setup', () => {
    const source = readFileSync('resources/js/Components/Footer.vue', 'utf8');

    assert.match(source, /typeof window !== ['"]undefined['"]/);
    assert.match(source, /watch\(currentLocale,\s*loadFooterData,\s*\{\s*immediate:\s*true\s*\}\)/);
});
