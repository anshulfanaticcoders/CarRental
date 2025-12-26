<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== LocautoRent Booking - Deep Analysis ===\n\n";

$pickupDate = date('Y-m-d', strtotime('+14 days'));
$returnDate = date('Y-m-d', strtotime('+19 days'));

echo "Dates: $pickupDate to $returnDate\n";
echo "Location: FCO (Rome Fiumicino Airport)\n\n";

$service = app(\App\Services\LocautoRentService::class);

// Get available vehicles first
echo "Getting available vehicles...\n";
$vehicleXml = $service->getVehicles("FCO", $pickupDate, "10:00", $returnDate, "10:00", 35, []);
$vehicles = $service->parseVehicleResponse($vehicleXml);

if (empty($vehicles)) {
    echo "No vehicles available!\n";
    exit(1);
}

$vehicle = $vehicles[0];
$sippCode = $vehicle['sipp_code'];
echo "Selected: $vehicle[brand] $vehicle[model] (SIPP: $sippCode)\n";

// Now try to make a booking using the makeReservation method from the service
echo "\n--- Testing makeReservation method ---\n";

$bookingData = [
    'pickup_date' => $pickupDate,
    'pickup_time' => '10:00',
    'return_date' => $returnDate,
    'return_time' => '10:00',
    'pickup_location_code' => 'FCO',
    'return_location_code' => 'FCO',
    'first_name' => 'Mario',
    'last_name' => 'Rossi',
    'sipp_code' => $sippCode,
    'extras' => []
];

$bookingResponse = $service->makeReservation($bookingData);

if ($bookingResponse) {
    echo "Got booking response!\n";
    echo "Response length: " . strlen($bookingResponse) . " bytes\n\n";

    echo "--- FULL RESPONSE ---\n";
    echo $bookingResponse . "\n\n";

    // Parse it deeply
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($bookingResponse);

    if ($xml) {
        $xml->registerXPathNamespace('s', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xml->registerXPathNamespace('ota', 'http://www.opentravel.org/OTA/2003/05');
        $xml->registerXPathNamespace('ns', 'https://nextrent.locautorent.com');

        echo "--- DEEP XML ANALYSIS ---\n";

        // Get ALL elements from body
        $body = $xml->xpath('//s:Body');
        if (!empty($body)) {
            echo "\nBody children:\n";
            foreach ($body[0]->children() as $name => $child) {
                echo "  - $name\n";
                print_r($child);
            }
        }

        // Get all attributes
        echo "\n--- ALL ATTRIBUTES IN RESPONSE ---\n";
        $allElements = $xml->xpath('//*');
        foreach ($allElements as $element) {
            $name = $element->getName();
            $hasAttrs = false;
            foreach ($element->attributes() as $attr => $value) {
                $hasAttrs = true;
                echo "  $name\@$attr = $value\n";
            }
            if ($hasAttrs) {
                echo "    Element value: " . trim((string)$element) . "\n";
            }
        }

        // Check for any ID fields
        echo "\n--- LOOKING FOR IDs/REFERENCES ---\n";
        $idFields = $xml->xpath('//*[@ConfID|@ConfirmID|@CreateDateTime|@ID|@ReservationStatus|@ReferenceID]');
        if (!empty($idFields)) {
            foreach ($idFields as $field) {
                $name = $field->getName();
                echo "Found $name:\n";
                foreach ($field->attributes() as $k => $v) {
                    echo "  $k = $v\n";
                }
            }
        } else {
            echo "No ID fields found in response\n";
        }

        // Check text content
        echo "\n--- ALL TEXT CONTENT ---\n";
        $textNodes = $xml->xpath('//text()');
        $texts = array_filter($textNodes, function($t) {
            return trim((string)$t) !== '';
        });
        foreach ($texts as $text) {
            echo "  " . trim((string)$text) . "\n";
        }
    }
} else {
    echo "No response received\n";
}

echo "\n=== Analysis Complete ===\n";
