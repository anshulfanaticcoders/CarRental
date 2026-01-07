<?php

/**
 * Adobe Cars API Complete Testing
 * Using correct date format with time
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$baseUrl = 'https://adobecar.cr:42800';
$username = 'Z11338';
$password = '11338';

echo "=== Adobe Cars API Complete Testing ===\n";
echo "Base URL: $baseUrl\n\n";

// ============================================================================
// Login
// ============================================================================
$loginResponse = Http::post($baseUrl . '/Auth/Login', [
    'userName' => $username,
    'password' => $password
]);

$token = $loginResponse->json('token');
echo "✓ Token obtained\n\n";

// ============================================================================
// Get Offices
// ============================================================================
$officesResponse = Http::withToken($token)->get($baseUrl . '/Offices');
$officesData = $officesResponse->json();
$testOffice = $officesData[0]['code'] ?? 'OCO';

echo "✓ Offices: " . count($officesData) . " offices\n";
file_put_contents(__DIR__ . '/adobe_offices.json', json_encode($officesData, JSON_PRETTY_PRINT));

// ============================================================================
// Get Availability with Price (using correct date format with time)
// ============================================================================
echo "Testing GetAvailabilityWithPrice...\n";

$availabilityResponse = Http::withToken($token)->get($baseUrl . '/Client/GetAvailabilityWithPrice', [
    'pickupOffice' => $testOffice,
    'returnOffice' => $testOffice,
    'startDate' => '2026-03-11 10:00',  // Date format WITH time
    'endDate' => '2026-03-20 10:00',      // Date format WITH time
    'customerCode' => 'Z11338'
]);

echo "  HTTP: " . $availabilityResponse->status() . "\n";

$availabilityData = $availabilityResponse->json();
file_put_contents(__DIR__ . '/adobe_availability.json', json_encode($availabilityData, JSON_PRETTY_PRINT));

if ($availabilityData && (!isset($availabilityData['success']) || $availabilityData['success'])) {
    echo "✓ Availability data obtained\n\n";
} else {
    echo "✗ Error: " . ($availabilityData['error'] ?? 'Unknown') . "\n\n";
}

// ============================================================================
// Get Category With Fare (Protections & Extras)
// ============================================================================
echo "Testing GetCategoryWithFare...\n";

$categoryResponse = Http::withToken($token)->get($baseUrl . '/Client/GetCategoryWithFare', [
    'pickupOffice' => $testOffice,
    'returnOffice' => $testOffice,
    'category' => 'ECMR',
    'startDate' => '2026-03-11 10:00',
    'endDate' => '2026-03-20 10:00',
    'customerCode' => 'Z11338',
    'idioma' => 'en'
]);

echo "  HTTP: " . $categoryResponse->status() . "\n";

$categoryData = $categoryResponse->json();
file_put_contents(__DIR__ . '/adobe_category.json', json_encode($categoryData, JSON_PRETTY_PRINT));

if (isset($categoryData['items'])) {
    echo "✓ Protections & Extras: " . count($categoryData['items']) . " items\n\n";

    $protections = array_filter($categoryData['items'], fn($i) => ($i['type'] ?? '') === 'Proteccion');
    $extras = array_filter($categoryData['items'], fn($i) => ($i['type'] ?? '') === 'Adicionales');

    echo "  Protections (Proteccion): " . count($protections) . "\n";
    echo "  Extras (Adicionales): " . count($extras) . "\n\n";
}

// ============================================================================
// Create Booking
// ============================================================================
echo "Testing Booking...\n";

$bookingResponse = Http::withToken($token)->post($baseUrl . '/Booking', [
    'pickupOffice' => $testOffice,
    'returnOffice' => $testOffice,
    'pickupDate' => '2026-03-11 10:00',
    'returnDate' => '2026-03-20 10:00',
    'category' => 'ECMR',
    'customerCode' => 'Z11338',
    'customerName' => 'Test Customer',
    'flightNumber' => '',
    'comment' => 'API Test'
]);

echo "  HTTP: " . $bookingResponse->status() . "\n";

$bookingData = $bookingResponse->json();
file_put_contents(__DIR__ . '/adobe_booking.json', json_encode($bookingData, JSON_PRETTY_PRINT));

if (isset($bookingData['result']) && $bookingData['result'] && isset($bookingData['data']['bookingNumber'])) {
    echo "✓ Booking created\n\n";

    $bookingNumber = $bookingData['data']['bookingNumber'];

    // Get booking details
    $detailsResponse = Http::withToken($token)->get($baseUrl . '/Booking', [
        'bookingNumber' => $bookingNumber,
        'customerCode' => 'Z11338'
    ]);

    $detailsData = $detailsResponse->json();
    file_put_contents(__DIR__ . '/adobe_booking_details.json', json_encode($detailsData, JSON_PRETTY_PRINT));
    echo "✓ Booking details obtained\n\n";
} else {
    echo "✗ Booking error: " . ($bookingData['error'] ?? 'Unknown') . "\n\n";
}

echo "\n=== Output Files ===\n";
echo "  - adobe_offices.json\n";
echo "  - adobe_availability.json\n";
echo "  - adobe_category.json\n";
echo "  - adobe_booking.json\n";
echo "  - adobe_booking_details.json\n";
