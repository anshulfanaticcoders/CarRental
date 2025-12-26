<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== LocautoRent COMPLETE Booking Test ===\n\n";

$service = app(\App\Services\LocautoRentService::class);

// Use dates at least 48 hours in the future
$pickupDate = date('Y-m-d', strtotime('+14 days'));
$returnDate = date('Y-m-d', strtotime('+19 days'));

echo "Using dates: $pickupDate to $returnDate\n";
echo "Location: FCO (Rome Fiumicino Airport)\n\n";

// Step 1: Get available vehicles first
echo "Step 1: Getting Vehicle Availability...\n";
$vehicleXml = $service->getVehicles("FCO", $pickupDate, "10:00", $returnDate, "10:00", 35, []);

if (!$vehicleXml) {
    echo "ERROR: No response from availability API\n";
    exit(1);
}

$vehicles = $service->parseVehicleResponse($vehicleXml);
echo "Found " . count($vehicles) . " available vehicles\n\n";

if (empty($vehicles)) {
    echo "ERROR: No vehicles available for these dates\n";
    exit(1);
}

// Use the first available vehicle
$selectedVehicle = $vehicles[0];
echo "Selected Vehicle for Booking:\n";
echo "  - Name: " . $selectedVehicle['brand'] . ' ' . $selectedVehicle['model'] . "\n";
echo "  - SIPP Code: " . $selectedVehicle['sipp_code'] . "\n";
echo "  - Total Price: €" . $selectedVehicle['total_amount'] . "\n\n";

// Step 2: Make a COMPLETE reservation with all details
echo "Step 2: Making Complete Reservation...\n";

$bookingData = [
    'pickup_date' => $pickupDate,
    'pickup_time' => '10:00',
    'return_date' => $returnDate,
    'return_time' => '10:00',
    'pickup_location_code' => 'FCO',
    'return_location_code' => 'FCO',
    'first_name' => 'Mario',
    'last_name' => 'Rossi',
    'email' => 'mario.rossi@example.com',
    'phone' => '+393351234567',
    'sipp_code' => $selectedVehicle['sipp_code'],
    'extras' => []
];

// Build enhanced XML request with complete info
$xml = '<?xml version="1.0" encoding="utf-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://www.opentravel.org/OTA/2003/05" xmlns:ns2="https://nextrent.locautorent.com">
  <SOAP-ENV:Body>
    <ns2:OTA_VehResRS>
      <ns1:OTA_VehResRQ EchoToken="BOOK_' . uniqid() . '" TimeStamp="' . now()->toISOString() . '" Target="Production" Version="1.0" RetransmitIndicator="false">
        <ns1:POS>
          <ns1:Source ISOCountry="IT" ISOCurrency="EUR">
            <ns1:RequestorID ID_Context="dpp_vrooem.com" MessagePassword="fssgfs99"/>
            <ns1:BookingChannel Type="7">
              <ns1:CompanyName>CarRental</ns1:CompanyName>
            </ns1:BookingChannel>
          </ns1:Source>
        </ns1:POS>
        <ns1:VehResRQCore>
          <ns1:VehRentalCore PickUpDateTime="' . $bookingData['pickup_date'] . 'T' . $bookingData['pickup_time'] . ':00+02:00" ReturnDateTime="' . $bookingData['return_date'] . 'T' . $bookingData['return_time'] . ':00+02:00">
            <ns1:PickUpLocation LocationCode="' . $bookingData['pickup_location_code'] . '"/>
            <ns1:ReturnLocation LocationCode="' . $bookingData['return_location_code'] . '"/>
          </ns1:VehRentalCore>
          <ns1:Customer>
            <ns1:Primary>
              <ns1:PersonName>
                <ns1:GivenName>' . htmlspecialchars($bookingData['first_name']) . '</ns1:GivenName>
                <ns1:Surname>' . htmlspecialchars($bookingData['last_name']) . '</ns1:Surname>
              </ns1:PersonName>
              <ns1:Telephone PhoneTechType="1" AreaCityCode="335" PhoneNumber="1234567" CountryAccessCode="39"/>
              <ns1:Email>' . htmlspecialchars($bookingData['email']) . '</ns1:Email>
              <ns1:Address>
                <ns1:AddressLine>Via Roma 123</ns1:AddressLine>
                <ns1:CityName>Rome</ns1:CityName>
                <ns1:PostalCode>00100</ns1:PostalCode>
                <ns1:CountryCode>IT</ns1:CountryCode>
              </ns1:Address>
              <ns1:CustLoyalty ProgramID="LOCAUTO" MembershipID="TEST_MEMBER_001"/>
            </ns1:Primary>
          </ns1:Customer>
          <ns1:VehPref Code="' . $bookingData['sipp_code'] . '" CodeContext="SIPP"/>
        </ns1:VehResRQCore>
        <ns1:VehResRQInfo>
          <ns1:RentalPaymentPref>
            <ns1:PaymentCard CardType="1" CardNumber="4111111111111111" SeriesCode="123">
              <ns1:CardHolderName>' . htmlspecialchars($bookingData['first_name'] . ' ' . $bookingData['last_name']) . '</ns1:CardHolderName>
              <ns1:CardCode>VI</nsCode>
              <ns1:EffectiveExpireDate EffectiveYear="2026" ExpireMonth="12"/>
            </ns1:PaymentCard>
          </ns1:RentalPaymentPref>
        </ns1:VehResRQInfo>
      </ns1:OTA_VehResRQ>
    </ns2:OTA_VehResRS>
  </SOAP-ENV:Body>
</SOAP-ENV:Envelope>';

echo "Sending booking request...\n";

// Send the request
$response = \Illuminate\Support\Facades\Http::withHeaders([
    'Content-Type' => 'text/xml; charset=utf-8',
    'SOAPAction' => '"https://nextrent.locautorent.com/OTA_VehResRS"',
])->send('POST', 'https://nextrent.locautorent.com/webservices/nextRentOTAService.asmx', [
    'body' => $xml,
]);

echo "HTTP Status: " . $response->status() . "\n";
$responseBody = $response->body();
echo "Response length: " . strlen($responseBody) . " bytes\n\n";

// Parse the response
echo "=== RAW BOOKING RESPONSE ===\n";
echo $responseBody . "\n\n";

// Try to extract booking reference
libxml_use_internal_errors(true);
$xmlResponse = simplexml_load_string($responseBody);

if ($xmlResponse !== false) {
    echo "=== PARSED RESPONSE ===\n";

    $xmlResponse->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
    $xmlResponse->registerXPathNamespace('ota', 'http://www.opentravel.org/OTA/2003/05');

    // Check for success
    $success = $xmlResponse->xpath('//ota:Success');
    if (!empty($success)) {
        echo "✅ Status: SUCCESS\n";
    }

    // Check for warnings
    $warnings = $xmlResponse->xpath('//ota:Warning');
    if (!empty($warnings)) {
        echo "⚠️  Warnings found:\n";
        foreach ($warnings as $warning) {
            echo "   - Type: " . $warning['Type'] . ", Text: " . $warning['ShortText'] . "\n";
        }
    }

    // Check for errors
    $errors = $xmlResponse->xpath('//ota:Error');
    if (!empty($errors)) {
        echo "❌ Errors found:\n";
        foreach ($errors as $error) {
            echo "   - Type: " . $error['Type'] . ", Text: " . $error['ShortText'] . "\n";
        }
    }

    // Try to get reservation details
    $reservations = $xmlResponse->xpath('//ota:VehReservation');
    if (!empty($reservations)) {
        echo "\n🎯 BOOKING CONFIRMATION:\n";
        foreach ($reservations as $res) {
            $conf = $res->VehSegmentCore;
            if ($conf) {
                echo "  Confirmation ID: " . ($conf['ConfirmID'] ?? $conf['CreateDateTime'] ?? 'N/A') . "\n";
                echo "  Status: " . ($conf['Status'] ?? 'N/A') . "\n";
            }
        }
    }

    // Try to get any reference numbers
    $uniqueRefs = $xmlResponse->xpath('//ota:UniqueID');
    if (!empty($uniqueRefs)) {
        echo "\n📋 Reference Numbers Found:\n";
        foreach ($uniqueRefs as $ref) {
            echo "  - ID: " . $ref . "\n";
            echo "    Type: " . $ref['Type'] . "\n";
            echo "    Context: " . $ref['ID_Context'] . "\n";
        }
    }

    // Get VehResRSCore details
    $resCore = $xmlResponse->xpath('//ota:VehResRSCore');
    if (!empty($resCore)) {
        echo "\n📄 Reservation Core Details:\n";
        foreach ($resCore[0]->children() as $key => $value) {
            echo "  $key: " . print_r($value, true) . "\n";
        }
    }
} else {
    echo "❌ Failed to parse XML response\n";
    foreach (libxml_get_errors() as $error) {
        echo "  " . $error->message . "\n";
    }
}

echo "\n=== Test Complete ===\n";
