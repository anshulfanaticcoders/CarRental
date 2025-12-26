<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== LocautoRent Booking Test - Multiple Approaches ===\n\n";

$pickupDate = date('Y-m-d', strtotime('+14 days'));
$returnDate = date('Y-m-d', strtotime('+19 days'));

echo "Using dates: $pickupDate to $returnDate\n";
echo "Location: FCO (Rome Fiumicino Airport)\n\n";

$service = app(\App\Services\LocautoRentService::class);

// First get available vehicles to get a valid SIPP code
echo "Step 1: Getting available vehicles...\n";
$vehicleXml = $service->getVehicles("FCO", $pickupDate, "10:00", $returnDate, "10:00", 35, []);
$vehicles = $service->parseVehicleResponse($vehicleXml);
echo "Found " . count($vehicles) . " vehicles\n";

if (empty($vehicles)) {
    echo "No vehicles available!\n";
    exit(1);
}

$sippCode = $vehicles[0]['sipp_code'];
echo "Using SIPP code: $sippCode (" . $vehicles[0]['brand'] . ' ' . $vehicles[0]['model'] . ")\n\n";

// Test different booking request formats
$tests = [
    'Test 1: Basic Booking (like original service)' => [
        'xml' => function() use ($pickupDate, $returnDate, $sippCode) {
            return '<?xml version="1.0" encoding="utf-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://www.opentravel.org/OTA/2003/05" xmlns:ns2="https://nextrent.locautorent.com">
  <SOAP-ENV:Body>
    <ns2:OTA_VehResRS>
      <ns1:OTA_VehResRQ EchoToken="TEST_' . time() . '" TimeStamp="' . date('c') . '" Target="Production" Version="1.0">
        <ns1:POS>
          <ns1:Source ISOCountry="IT" ISOCurrency="EUR">
            <ns1:RequestorID ID_Context="dpp_vrooem.com" MessagePassword="fssgfs99"/>
          </ns1:Source>
        </ns1:POS>
        <ns1:VehResRQCore>
          <ns1:VehRentalCore PickUpDateTime="' . $pickupDate . 'T10:00:00+02:00" ReturnDateTime="' . $returnDate . 'T10:00:00+02:00">
            <ns1:PickUpLocation LocationCode="FCO"/>
            <ns1:ReturnLocation LocationCode="FCO"/>
          </ns1:VehRentalCore>
          <ns1:Customer>
            <ns1:Primary>
              <ns1:PersonName>
                <ns1:GivenName>Mario</ns1:GivenName>
                <ns1:Surname>Rossi</ns1:Surname>
              </ns1:PersonName>
            </ns1:Primary>
          </ns1:Customer>
          <ns1:VehPref Code="' . $sippCode . '" CodeContext="SIPP"/>
        </ns1:VehResRQCore>
      </ns1:OTA_VehResRQ>
    </ns2:OTA_VehResRS>
  </SOAP-ENV:Body>
</SOAP-ENV:Envelope>';
        }
    ],

    'Test 2: With Contact Details' => [
        'xml' => function() use ($pickupDate, $returnDate, $sippCode) {
            return '<?xml version="1.0" encoding="utf-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://www.opentravel.org/OTA/2003/05" xmlns:ns2="https://nextrent.locautorent.com">
  <SOAP-ENV:Body>
    <ns2:OTA_VehResRS>
      <ns1:OTA_VehResRQ EchoToken="TEST_' . time() . '" TimeStamp="' . date('c') . '" Target="Production" Version="1.0">
        <ns1:POS>
          <ns1:Source ISOCountry="IT" ISOCurrency="EUR">
            <ns1:RequestorID ID_Context="dpp_vrooem.com" MessagePassword="fssgfs99"/>
          </ns1:Source>
        </ns1:POS>
        <ns1:VehResRQCore>
          <ns1:VehRentalCore PickUpDateTime="' . $pickupDate . 'T10:00:00+02:00" ReturnDateTime="' . $returnDate . 'T10:00:00+02:00">
            <ns1:PickUpLocation LocationCode="FCO"/>
            <ns1:ReturnLocation LocationCode="FCO"/>
          </ns1:VehRentalCore>
          <ns1:Customer>
            <ns1:Primary>
              <ns1:PersonName>
                <ns1:GivenName>Mario</ns1:GivenName>
                <ns1:Surname>Rossi</ns1:Surname>
              </ns1:PersonName>
              <ns1:Telephone PhoneNumber="+393351234567"/>
              <ns1:Email>mario.rossi@example.com</ns1:Email>
            </ns1:Primary>
          </ns1:Customer>
          <ns1:VehPref Code="' . $sippCode . '" CodeContext="SIPP"/>
        </ns1:VehResRQCore>
      </ns1:OTA_VehResRQ>
    </ns2:OTA_VehResRS>
  </SOAP-ENV:Body>
</SOAP-ENV:Envelope>';
        }
    ],

    'Test 3: With RentalPaymentPref (No Card Number)' => [
        'xml' => function() use ($pickupDate, $returnDate, $sippCode) {
            return '<?xml version="1.0" encoding="utf-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://www.opentravel.org/OTA/2003/05" xmlns:ns2="https://nextrent.locautorent.com">
  <SOAP-ENV:Body>
    <ns2:OTA_VehResRS>
      <ns1:OTA_VehResRQ EchoToken="TEST_' . time() . '" TimeStamp="' . date('c') . '" Target="Production" Version="1.0">
        <ns1:POS>
          <ns1:Source ISOCountry="IT" ISOCurrency="EUR">
            <ns1:RequestorID ID_Context="dpp_vrooem.com" MessagePassword="fssgfs99"/>
          </ns1:Source>
        </ns1:POS>
        <ns1:VehResRQCore>
          <ns1:VehRentalCore PickUpDateTime="' . $pickupDate . 'T10:00:00+02:00" ReturnDateTime="' . $returnDate . 'T10:00:00+02:00">
            <ns1:PickUpLocation LocationCode="FCO"/>
            <ns1:ReturnLocation LocationCode="FCO"/>
          </ns1:VehRentalCore>
          <ns1:Customer>
            <ns1:Primary>
              <ns1:PersonName>
                <ns1:GivenName>Mario</ns1:GivenName>
                <ns1:Surname>Rossi</ns1:Surname>
              </ns1:PersonName>
              <ns1:Telephone PhoneNumber="+393351234567"/>
              <ns1:Email>mario.rossi@example.com</ns1:Email>
            </ns1:Primary>
          </ns1:Customer>
          <ns1:VehPref Code="' . $sippCode . '" CodeContext="SIPP"/>
        </ns1:VehResRQCore>
        <ns1:VehResRQInfo>
          <ns1:RentalPaymentPref PaymentType="1"/>
        </ns1:VehResRQInfo>
      </ns1:OTA_VehResRQ>
    </ns2:OTA_VehResRS>
  </SOAP-ENV:Body>
</SOAP-ENV:Envelope>';
        }
    ],
];

foreach ($tests as $testName => $test) {
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "$testName\n";
    echo str_repeat("=", 60) . "\n";

    $xml = $test['xml']();

    try {
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Content-Type' => 'text/xml; charset=utf-8',
            'SOAPAction' => '"https://nextrent.locautorent.com/OTA_VehResRS"',
        ])->send('POST', 'https://nextrent.locautorent.com/webservices/nextRentOTAService.asmx', [
            'body' => $xml,
        ]);

        echo "HTTP Status: " . $response->status() . " " . $response->reason() . "\n";
        $responseBody = $response->body();

        if (empty($responseBody)) {
            echo "Response: EMPTY\n";
            continue;
        }

        echo "Response length: " . strlen($responseBody) . " bytes\n";
        echo "\n--- Raw Response (first 1000 chars) ---\n";
        echo substr($responseBody, 0, 1000) . "\n";

        // Parse and extract booking info
        libxml_use_internal_errors(true);
        $xmlResponse = simplexml_load_string($responseBody);

        if ($xmlResponse !== false) {
            $xmlResponse->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
            $xmlResponse->registerXPathNamespace('ota', 'http://www.opentravel.org/OTA/2003/05');

            echo "\n--- Parsed Details ---\n";

            // Check for success
            $success = $xmlResponse->xpath('//ota:Success');
            if (!empty($success)) {
                echo "✅ Status: SUCCESS\n";
            }

            // Check for errors
            $errors = $xmlResponse->xpath('//ota:Error');
            if (!empty($errors)) {
                echo "❌ Errors:\n";
                foreach ($errors as $error) {
                    echo "   - " . $error['ShortText'] . "\n";
                }
            }

            // Check for warnings
            $warnings = $xmlResponse->xpath('//ota:Warning');
            if (!empty($warnings)) {
                echo "⚠️  Warnings:\n";
                foreach ($warnings as $warning) {
                    echo "   - " . $warning['ShortText'] . "\n";
                }
            }

            // Look for reservation/booking reference
            $confId = $xmlResponse->xpath('//ota:VehResRSCore/@ConfID | //ota:VehSegmentCore/@ConfirmID | //ota:UniqueID');
            if (!empty($confId)) {
                echo "\n🎯 BOOKING REFERENCE FOUND:\n";
                foreach ($confId as $id) {
                    echo "   " . $id . "\n";
                }
            }

            // Get VehResRSCore content
            $resCore = $xmlResponse->xpath('//ota:VehResRSCore');
            if (!empty($resCore)) {
                echo "\n📋 Reservation Core:\n";
                $core = $resCore[0];
                foreach ($core->attributes() as $name => $value) {
                    echo "   @$name = $value\n";
                }
            }
        }

    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }

    echo "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "All tests complete!\n";
