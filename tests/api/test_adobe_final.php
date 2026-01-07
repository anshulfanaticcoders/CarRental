<?php

/**
 * Adobe Cars API Final Testing
 * Using proper date ranges from production logs
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$baseUrl = 'https://adobecar.cr:42800';
$username = 'Z11338';
$password = '11338';

echo "=== Adobe Cars API Final Testing ===\n";
echo "Base URL: $baseUrl\n\n";

// ============================================================================
// Login
// ============================================================================
$loginResponse = Http::post($baseUrl . '/Auth/Login', [
    'userName' => $username,
    'password' => $password
]);

$token = $loginResponse->json('token');
if (!$token) {
    echo "ERROR: Failed to get token!\n";
    exit(1);
}
echo "✓ Token obtained\n\n";

// ============================================================================
// Get Offices
// ============================================================================
$officesResponse = Http::withToken($token)->get($baseUrl . '/Offices');
$officesData = $officesResponse->json();
$testOffice = $officesData[0]['code'] ?? 'OCO';

echo "✓ Offices obtained: " . count($officesData) . " offices\n";
echo "  Using test office: $testOffice\n\n";

// ============================================================================
// Get Availability (using dates from production logs that worked)
// ============================================================================
echo "Testing GetAvailabilityWithPrice...\n";

$availabilityResponse = Http::withToken($token)->get($baseUrl . '/Client/GetAvailabilityWithPrice', [
    'pickupOffice' => $testOffice,
    'returnOffice' => $testOffice,
    'startDate' => '2026-02-15',
    'endDate' => '2026-02-22',
    'customerCode' => 'Z11338'
]);

echo "  HTTP Code: " . $availabilityResponse->status() . "\n";
echo "  Body: " . $availabilityResponse->body() . "\n\n";

$availabilityData = $availabilityResponse->json();
file_put_contents(__DIR__ . '/adobe_availability.json', json_encode($availabilityData, JSON_PRETTY_PRINT));

// ============================================================================
// Get Category With Fare (Protections & Extras)
// ============================================================================
echo "Testing GetCategoryWithFare...\n";

$categoryResponse = Http::withToken($token)->get($baseUrl . '/Client/GetCategoryWithFare', [
    'pickupOffice' => $testOffice,
    'returnOffice' => $testOffice,
    'category' => 'ECMR',
    'startDate' => '2026-02-15',
    'endDate' => '2026-02-22',
    'customerCode' => 'Z11338',
    'idioma' => 'en'
]);

echo "  HTTP Code: " . $categoryResponse->status() . "\n";
echo "  Body length: " . strlen($categoryResponse->body()) . " bytes\n\n";

$categoryData = $categoryResponse->json();
file_put_contents(__DIR__ . '/adobe_category.json', json_encode($categoryData, JSON_PRETTY_PRINT));

if (isset($categoryData['items'])) {
    echo "✓ Protections & Extras obtained: " . count($categoryData['items']) . " items\n\n";

    $protections = array_filter($categoryData['items'], fn($i) => ($i['type'] ?? '') === 'Proteccion');
    $extras = array_filter($categoryData['items'], fn($i) => ($i['type'] ?? '') === 'Adicionales');

    echo "  Protections: " . count($protections) . "\n";
    echo "  Extras: " . count($extras) . "\n\n";
}

// ============================================================================
// Create Booking
// ============================================================================
echo "Testing Booking...\n";

$bookingResponse = Http::withToken($token)->post($baseUrl . '/Booking', [
    'pickupOffice' => $testOffice,
    'returnOffice' => $testOffice,
    'pickupDate' => '2026-02-15',
    'returnDate' => '2026-02-22',
    'category' => 'ECMR',
    'customerCode' => 'Z11338',
    'customerName' => 'Test Customer',
    'flightNumber' => '',
    'comment' => 'API Test'
]);

echo "  HTTP Code: " . $bookingResponse->status() . "\n";
echo "  Body: " . $bookingResponse->body() . "\n\n";

$bookingData = $bookingResponse->json();
file_put_contents(__DIR__ . '/adobe_booking.json', json_encode($bookingData, JSON_PRETTY_PRINT));

if (isset($bookingData['result']) && $bookingData['result']) {
    echo "✓ Booking created!\n\n";

    $bookingNumber = $bookingData['data']['bookingNumber'] ?? null;
    if ($bookingNumber) {
        echo "  Booking Number: $bookingNumber\n\n";

        // Get booking details
        $detailsResponse = Http::withToken($token)->get($baseUrl . '/Booking', [
            'bookingNumber' => $bookingNumber,
            'customerCode' => 'Z11338'
        ]);

        echo "  Details HTTP Code: " . $detailsResponse->status() . "\n";
        $detailsData = $detailsResponse->json();
        file_put_contents(__DIR__ . '/adobe_booking_details.json', json_encode($detailsData, JSON_PRETTY_PRINT));
        echo "✓ Booking details obtained\n\n";
    }
}

echo "\n=== All tests complete ===\n";
