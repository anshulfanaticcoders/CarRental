<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== LocautoRent Booking Test ===\n\n";

$service = app(\App\Services\LocautoRentService::class);

// Use dates at least 48 hours in the future as required by many rental APIs
$pickupDate = date('Y-m-d', strtotime('+7 days'));
$returnDate = date('Y-m-d', strtotime('+12 days'));

echo "Using dates: $pickupDate to $returnDate\n\n";

// Test 1: Get Locations
echo "Test 1: Getting Locations...\n";
$locations = $service->parseLocationResponse();
echo "Found " . count($locations) . " locations\n";
echo "First location: " . json_encode($locations[0] ?? null, JSON_PRETTY_PRINT) . "\n\n";

// Test 2: Get Vehicles (Availability)
echo "Test 2: Getting Vehicle Availability for FCO (Rome Fiumicino Airport)...\n";
echo "Request: FCO, $pickupDate 10:00 to $returnDate 10:00\n";

$vehicleXml = $service->getVehicles("FCO", $pickupDate, "10:00", $returnDate, "10:00", 35, []);

if ($vehicleXml) {
    echo "Got XML response (length: " . strlen($vehicleXml) . " bytes)\n";

    $vehicles = $service->parseVehicleResponse($vehicleXml);
    echo "Parsed " . count($vehicles) . " vehicles\n";

    if (!empty($vehicles)) {
        echo "\n--- First Vehicle ---\n";
        echo json_encode($vehicles[0], JSON_PRETTY_PRINT) . "\n";
    }
} else {
    echo "No response from API (check Laravel logs)\n";
}

echo "\n";

// Test 3: Make a Reservation
echo "Test 3: Making a Test Reservation...\n";

$bookingData = [
    'pickup_date' => $pickupDate,
    'pickup_time' => '10:00',
    'return_date' => $returnDate,
    'return_time' => '10:00',
    'pickup_location_code' => 'FCO',
    'return_location_code' => 'FCO',
    'first_name' => 'Test',
    'last_name' => 'Customer',
    'sipp_code' => 'C',
    'extras' => []
];

$bookingXml = $service->makeReservation($bookingData);

if ($bookingXml) {
    echo "Got booking response (length: " . strlen($bookingXml) . " bytes)\n";
    echo "\n=== Raw Booking Response ===\n";
    echo $bookingXml . "\n";

    // Pretty print if it's valid XML
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($bookingXml);
    if ($xml) {
        echo "\n=== Formatted XML ===\n";
        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        echo $dom->saveXML();
    }
} else {
    echo "No response from API (check Laravel logs)\n";
}

echo "\n=== Test Complete ===\n";
