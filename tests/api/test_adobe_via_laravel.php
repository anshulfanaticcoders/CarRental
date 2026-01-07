<?php

/**
 * Adobe Cars API Testing via Laravel
 * Uses Laravel's HTTP client to match production behavior
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$baseUrl = 'https://adobecar.cr:42800';
$username = 'Z11338';
$password = '11338';

echo "=== Adobe Cars API Testing via Laravel HTTP ===\n";
echo "Base URL: $baseUrl\n\n";

// ============================================================================
// ENDPOINT 1: Login
// ============================================================================
echo "================================================\n";
echo "ENDPOINT 1: /Auth/Login\n";
echo "================================================\n";

$loginResponse = Http::post($baseUrl . '/Auth/Login', [
    'userName' => $username,
    'password' => $password
]);

echo "HTTP Code: " . $loginResponse->status() . "\n";
echo "Response:\n" . json_encode($loginResponse->json(), JSON_PRETTY_PRINT) . "\n\n";

$token = $loginResponse->json('token');
if (!$token) {
    echo "ERROR: Failed to get token!\n";
    exit(1);
}

echo "✓ Token obtained\n\n";

// ============================================================================
// ENDPOINT 2: Offices
// ============================================================================
echo "================================================\n";
echo "ENDPOINT 2: /Offices\n";
echo "================================================\n";

$officesResponse = Http::withToken($token)->get($baseUrl . '/Offices');

echo "HTTP Code: " . $officesResponse->status() . "\n";
$officesData = $officesResponse->json();
echo "Offices Count: " . count($officesData) . "\n";
echo "First Office:\n" . json_encode($officesData[0] ?? [], JSON_PRETTY_PRINT) . "\n\n";

file_put_contents(__DIR__ . '/adobe_offices.json', json_encode($officesData, JSON_PRETTY_PRINT));

$testOffice = $officesData[0]['code'] ?? 'OCO';
$startDate = now()->addDay()->format('Y-m-d');
$endDate = now()->addDays(7)->format('Y-m-d');

echo "Using office: $testOffice\n";
echo "Date range: $startDate to $endDate\n\n";

// ============================================================================
// ENDPOINT 3: GetAvailabilityWithPrice
// ============================================================================
echo "================================================\n";
echo "ENDPOINT 3: /Client/GetAvailabilityWithPrice\n";
echo "================================================\n";

$availabilityResponse = Http::withToken($token)->get($baseUrl . '/Client/GetAvailabilityWithPrice', [
    'pickupOffice' => $testOffice,
    'returnOffice' => $testOffice,
    'startDate' => $startDate,
    'endDate' => $endDate,
    'customerCode' => 'Z11338'
]);

echo "HTTP Code: " . $availabilityResponse->status() . "\n";
echo "Response Body Length: " . strlen($availabilityResponse->body()) . "\n\n";

$availabilityData = $availabilityResponse->json();
echo "Response:\n" . json_encode($availabilityData, JSON_PRETTY_PRINT) . "\n\n";

file_put_contents(__DIR__ . '/adobe_availability.json', json_encode($availabilityData, JSON_PRETTY_PRINT));

// ============================================================================
// ENDPOINT 4: GetCategoryWithFare
// ============================================================================
echo "================================================\n";
echo "ENDPOINT 4: /Client/GetCategoryWithFare\n";
echo "================================================\n";

$categoryResponse = Http::withToken($token)->get($baseUrl . '/Client/GetCategoryWithFare', [
    'pickupOffice' => $testOffice,
    'returnOffice' => $testOffice,
    'category' => 'ECMR',
    'startDate' => $startDate,
    'endDate' => $endDate,
    'customerCode' => 'Z11338',
    'idioma' => 'en'
]);

echo "HTTP Code: " . $categoryResponse->status() . "\n";
echo "Response Body Length: " . strlen($categoryResponse->body()) . "\n\n";

$categoryData = $categoryResponse->json();
echo "Response:\n" . json_encode($categoryData, JSON_PRETTY_PRINT) . "\n\n";

file_put_contents(__DIR__ . '/adobe_category.json', json_encode($categoryData, JSON_PRETTY_PRINT));

// ============================================================================
// ENDPOINT 5: Create Booking
// ============================================================================
echo "================================================\n";
echo "ENDPOINT 5: /Booking (POST)\n";
echo "================================================\n";

$bookingResponse = Http::withToken($token)->post($baseUrl . '/Booking', [
    'pickupOffice' => $testOffice,
    'returnOffice' => $testOffice,
    'pickupDate' => $startDate,
    'returnDate' => $endDate,
    'category' => 'ECMR',
    'customerCode' => 'Z11338',
    'customerName' => 'Test Customer',
    'flightNumber' => '',
    'comment' => 'API Test Booking'
]);

echo "HTTP Code: " . $bookingResponse->status() . "\n";
echo "Response:\n" . json_encode($bookingResponse->json(), JSON_PRETTY_PRINT) . "\n\n";

$bookingData = $bookingResponse->json();
file_put_contents(__DIR__ . '/adobe_booking.json', json_encode($bookingData, JSON_PRETTY_PRINT));

// ============================================================================
// ENDPOINT 6: Get Booking Details
// ============================================================================
if (isset($bookingData['data']['bookingNumber']) || isset($bookingData['bookingNumber'])) {
    $bookingNumber = $bookingData['data']['bookingNumber'] ?? $bookingData['bookingNumber'] ?? null;

    if ($bookingNumber) {
        echo "================================================\n";
        echo "ENDPOINT 6: /Booking (GET)\n";
        echo "================================================\n";
        echo "Booking Number: $bookingNumber\n\n";

        $detailsResponse = Http::withToken($token)->get($baseUrl . '/Booking', [
            'bookingNumber' => $bookingNumber,
            'customerCode' => 'Z11338'
        ]);

        echo "HTTP Code: " . $detailsResponse->status() . "\n";
        echo "Response:\n" . json_encode($detailsResponse->json(), JSON_PRETTY_PRINT) . "\n\n";

        file_put_contents(__DIR__ . '/adobe_booking_details.json', json_encode($detailsResponse->json(), JSON_PRETTY_PRINT));
    }
}

echo "\n================================================\n";
echo "=== ALL TESTS COMPLETE ===\n";
echo "================================================\n";
echo "\nOutput files:\n";
echo "  - adobe_offices.json\n";
echo "  - adobe_availability.json\n";
echo "  - adobe_category.json\n";
echo "  - adobe_booking.json\n";
echo "  - adobe_booking_details.json\n";
