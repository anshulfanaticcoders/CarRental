import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import path from 'node:path';

const repoRoot = path.resolve(process.cwd());

const read = (relativePath) => readFileSync(path.join(repoRoot, relativePath), 'utf8');

test('affiliate admin pages expose a delete action with a confirmation dialog and loading state', () => {
    const partners = read('resources/js/Pages/AdminDashboardPages/Affiliate/Partners.vue');
    const partnerDetail = read('resources/js/Pages/AdminDashboardPages/Affiliate/PartnerDetail.vue');

    assert.match(partners, /admin\.affiliate\.partners\.destroy/);
    assert.match(partners, /Delete/);
    assert.match(partners, /Deleting\.\.\./);
    assert.match(partners, /AlertDialog/);

    assert.match(partnerDetail, /admin\.affiliate\.partners\.destroy/);
    assert.match(partnerDetail, /Delete/);
    assert.match(partnerDetail, /Deleting\.\.\./);
    assert.match(partnerDetail, /AlertDialog/);
});
