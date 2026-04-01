import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import path from 'node:path';

const repoRoot = path.resolve(process.cwd());
const read = (relativePath) => readFileSync(path.join(repoRoot, relativePath), 'utf8');

test('register page gives the DOB datepicker menu a high z-index so it renders above form actions', () => {
    const registerPage = read('resources/js/Pages/Auth/Register.vue');

    assert.match(registerPage, /:deep\(\.dp__menu\)\s*\{/);
    assert.match(registerPage, /z-index:\s*9999\s*!important;/);
});
