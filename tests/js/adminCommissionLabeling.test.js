import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import path from 'node:path';

const repoRoot = path.resolve(process.cwd());

const read = (relativePath) => readFileSync(path.join(repoRoot, relativePath), 'utf8');

test('admin overview pages describe admin amounts as commission earnings instead of generic revenue', () => {
    const overview = read('resources/js/Pages/AdminDashboardPages/Overview/Index.vue');
    const analytics = read('resources/js/Pages/AdminDashboardPages/Analytics/Index.vue');
    const businessReports = read('resources/js/Pages/AdminDashboardPages/BusinessReports/Index.vue');
    const payments = read('resources/js/Pages/AdminDashboardPages/Payments/Index.vue');
    const bookings = read('resources/js/Pages/AdminDashboardPages/Bookings/Index.vue');

    assert.match(overview, /Total Commission Revenue/);
    assert.match(overview, /Recent Commission Earnings/);
    assert.doesNotMatch(overview, /Total Revenue/);
    assert.doesNotMatch(overview, /Recent Sales/);

    assert.match(analytics, /Total Commission Revenue/);
    assert.match(analytics, /Commission Collected/);
    assert.match(analytics, /Pending Commission/);
    assert.match(analytics, /Commission Momentum/);
    assert.match(analytics, /Commission Breakdown/);
    assert.doesNotMatch(analytics, /Paid Revenue/);
    assert.doesNotMatch(analytics, /Pending Revenue/);
    assert.doesNotMatch(analytics, /Revenue Breakdown/);

    assert.match(businessReports, /Commission revenue, bookings, and fleet metrics/);
    assert.match(businessReports, /Total Commission Revenue/);
    assert.match(businessReports, /Commission & Bookings/);
    assert.match(businessReports, /Commission by Location/);
    assert.doesNotMatch(businessReports, /Revenue, bookings, and fleet metrics/);
    assert.doesNotMatch(businessReports, /Revenue & Bookings/);
    assert.doesNotMatch(businessReports, /Revenue by Location/);

    assert.match(payments, /Total Commission/);
    assert.match(payments, /Commission Total/);
    assert.match(payments, /Commission Collected/);
    assert.match(payments, /Commission Pending/);
    assert.doesNotMatch(payments, /Admin Total/);
    assert.doesNotMatch(payments, /Admin Paid/);
    assert.doesNotMatch(payments, /Admin Pending/);
    assert.doesNotMatch(payments, /Total Amount/);

    assert.match(bookings, />Commission</);
    assert.match(bookings, /Commission Collected:/);
    assert.match(bookings, /Pending Commission:/);
    assert.doesNotMatch(bookings, />Amount</);
    assert.doesNotMatch(bookings, /Paid:/);
    assert.doesNotMatch(bookings, /Pending:/);
});
