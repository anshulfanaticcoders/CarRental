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
    assert.match(partnerDetail, /admin\.affiliate\.partners\.qrcodes\.destroy|admin\.affiliate\.partners\.qr-codes\.destroy/);
    assert.match(partnerDetail, /Delete/);
    assert.match(partnerDetail, /Deleting\.\.\./);
    assert.match(partnerDetail, /AlertDialog/);
});

test('affiliate admin quick search does not expose removed legacy affiliate pages', () => {
    const layout = read('resources/js/Layouts/AdminDashboardLayout.vue');

    assert.doesNotMatch(layout, /\/admin\/affiliate\/business-statistics/);
    assert.doesNotMatch(layout, /\/admin\/affiliate\/business-verification/);
    assert.doesNotMatch(layout, /\/admin\/affiliate\/payment-tracking/);
    assert.doesNotMatch(layout, /\/admin\/affiliate\/commission-management/);
    assert.doesNotMatch(layout, /\/admin\/affiliate\/qr-analytics/);
    assert.doesNotMatch(layout, /\/admin\/affiliate\/business-register/);
});

test('affiliate QR page exposes delete route and localized share URL', () => {
    const qrCodes = read('resources/js/Pages/Affiliate/QrCodes.vue');

    assert.match(qrCodes, /affiliate\.qr-codes\.destroy/);
    assert.match(qrCodes, /deleteQrCode/);
    assert.match(qrCodes, /\$\{window\.location\.origin\}\/\$\{locale\.value\}\/affiliate\/qr\/\$\{qr\.short_code\}/);
});

test('affiliate QR create form exposes location validation instead of silently no-oping', () => {
    const qrCodes = read('resources/js/Pages/Affiliate/QrCodes.vue');

    assert.match(qrCodes, /openCreateForm/);
    assert.match(qrCodes, /selectedLocation\.value = props\.locations\?\.\[0\]\?\.id \|\| 'new'/);
    assert.match(qrCodes, /qrForm\.errors\.location_id/);
    assert.match(qrCodes, /qrForm\.errors\.qr_code/);
    assert.match(qrCodes, /v-model="qrForm\.latitude"/);
    assert.match(qrCodes, /v-model="qrForm\.longitude"/);
});
