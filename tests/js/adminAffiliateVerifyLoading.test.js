import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import path from 'node:path';

const repoRoot = path.resolve(process.cwd());

const read = (relativePath) => readFileSync(path.join(repoRoot, relativePath), 'utf8');

test('affiliate admin pages show a verifying loading state while verify requests are running', () => {
    const partners = read('resources/js/Pages/AdminDashboardPages/Affiliate/Partners.vue');
    const partnerDetail = read('resources/js/Pages/AdminDashboardPages/Affiliate/PartnerDetail.vue');

    assert.match(partners, /Verifying\.\.\./);
    assert.match(partners, /verifyingPartnerId/);
    assert.match(partners, /:disabled=/);

    assert.match(partnerDetail, /Verifying\.\.\./);
    assert.match(partnerDetail, /isVerifying/);
    assert.match(partnerDetail, /:disabled=/);
});
