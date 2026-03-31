import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import path from 'node:path';

const repoRoot = path.resolve(process.cwd());
const read = (relativePath) => readFileSync(path.join(repoRoot, relativePath), 'utf8');

test('booking details and success pages use the same DM Sans / Outfit typography system as search surfaces', () => {
    const bookingDetails = read('resources/js/Pages/Booking/BookingDetails.vue');
    const bookingSuccess = read('resources/js/Pages/Booking/Success.vue');

    assert.match(bookingDetails, /@import url\('https:\/\/fonts\.googleapis\.com\/css2\?family=Outfit/);
    assert.match(bookingDetails, /class="booking-details-page/);
    assert.match(bookingDetails, /\.booking-details-page \{/);
    assert.match(bookingDetails, /font-family: 'DM Sans', sans-serif;/);
    assert.match(bookingDetails, /\.booking-details-page :deep\(h1\)/);
    assert.match(bookingDetails, /font-family: 'Outfit', sans-serif;/);

    assert.match(bookingSuccess, /@import url\('https:\/\/fonts\.googleapis\.com\/css2\?family=Outfit/);
    assert.match(bookingSuccess, /class="booking-success-page/);
    assert.match(bookingSuccess, /\.booking-success-page \{/);
    assert.match(bookingSuccess, /font-family: 'DM Sans', sans-serif;/);
    assert.match(bookingSuccess, /\.booking-success-page :deep\(h2\)/);
    assert.match(bookingSuccess, /font-family: 'Outfit', sans-serif;/);
});
