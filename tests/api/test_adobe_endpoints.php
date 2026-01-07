<?php

/**
 * Adobe Cars API Endpoint Testing Script
 * Tests all available endpoints and documents the response structure
 */

// Adobe API Configuration
$baseUrl = 'https://adobecar.cr:42800';
$username = 'Z11338';
$password = '11338';

echo "=== Adobe Cars API Endpoint Testing ===\n";
echo "Base URL: $baseUrl\n";
echo "Username: $username\n\n";

// Helper function to make API requests
function makeRequest($method, $endpoint, $data = null, $token = null) {
    global $baseUrl;

    $url = rtrim($baseUrl, '/') . $endpoint;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            if ($token && $endpoint === '/Client/GetAvailabilityWithPrice') {
                // For GetAvailabilityWithPrice, send as form data
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            }
        }
    } elseif ($method === 'GET' && $data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }

    if ($token) {
        $headers = ['Authorization: Bearer ' . $token];
        if ($endpoint !== '/Client/GetAvailabilityWithPrice') {
            $headers[] = 'Content-Type: application/json';
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'code' => $httpCode,
        'body' => $response
    ];
}

// Helper to format JSON output
function formatJson($json) {
    $decoded = json_decode($json, true);
    if ($decoded === null) {
        return $json;
    }
    return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

// ============================================================================
// ENDPOINT 1: /Auth/Login - Get Access Token
// ============================================================================
echo "================================================\n";
echo "ENDPOINT 1: /Auth/Login\n";
echo "================================================\n";

$loginResponse = makeRequest('POST', '/Auth/Login', [
    'userName' => $username,
    'password' => $password
]);

echo "HTTP Code: {$loginResponse['code']}\n";
echo "Response:\n";
$loginData = json_decode($loginResponse['body'], true);
echo formatJson($loginResponse['body']) . "\n\n";

$token = $loginData['token'] ?? null;
if (!$token) {
    echo "ERROR: Failed to get access token!\n";
    exit(1);
}

echo "✓ Access Token obtained: " . substr($token, 0, 20) . "...\n\n";

// ============================================================================
// ENDPOINT 2: /Offices - Get Office List (Locations)
// ============================================================================
echo "================================================\n";
echo "ENDPOINT 2: /Offices - Get Office List\n";
echo "================================================\n";

$officesResponse = makeRequest('GET', '/Offices', null, $token);

echo "HTTP Code: {$officesResponse['code']}\n";
echo "Response Length: " . strlen($officesResponse['body']) . " bytes\n\n";

$officesData = json_decode($officesResponse['body'], true);
echo "Offices Count: " . count($officesData ?? []) . "\n";
echo "First Office:\n";
echo formatJson(json_encode($officesData[0] ?? [])) . "\n\n";

// Save full offices response to file
file_put_contents(__DIR__ . '/adobe_offices_response.json', formatJson($officesResponse['body']));
echo "✓ Full offices response saved to: adobe_offices_response.json\n\n";

// ============================================================================
// ENDPOINT 3: /Client/GetAvailabilityWithPrice - Get Available Vehicles (POST)
// ============================================================================
echo "================================================\n";
echo "ENDPOINT 3: /Client/GetAvailabilityWithPrice (POST)\n";
echo "================================================\n";

// Use first office code for testing
$testOfficeCode = $officesData[0]['code'] ?? 'OCO';
$startDate = date('Y-m-d'); // Today
$endDate = date('Y-m-d', strtotime('+7 days')); // 7 days from now

echo "Test Parameters:\n";
echo "  pickupOffice: $testOfficeCode\n";
echo "  returnOffice: $testOfficeCode\n";
echo "  startDate: $startDate\n";
echo "  endDate: $endDate\n";
echo "  customerCode: Z11338\n\n";

$availabilityParams = [
    'pickupOffice' => $testOfficeCode,
    'returnOffice' => $testOfficeCode,
    'startDate' => $startDate,
    'endDate' => $endDate,
    'customerCode' => 'Z11338'
];

$availabilityResponse = makeRequest('POST', '/Client/GetAvailabilityWithPrice', $availabilityParams, $token);

echo "HTTP Code: {$availabilityResponse['code']}\n";
echo "Response Length: " . strlen($availabilityResponse['body']) . " bytes\n\n";

$availabilityData = json_decode($availabilityResponse['body'], true);
echo "Response:\n";
echo formatJson($availabilityResponse['body']) . "\n\n";

if (isset($availabilityData) && is_array($availabilityData) && count($availabilityData) > 0) {
    echo "Total vehicles: " . count($availabilityData) . "\n";
}

// Save full availability response
file_put_contents(__DIR__ . '/adobe_availability_response.json', formatJson($availabilityResponse['body']));
echo "✓ Full availability response saved to: adobe_availability_response.json\n\n";

// ============================================================================
// ENDPOINT 4: /Client/GetCategoryWithFare - Get Protections and Extras
// ============================================================================
echo "================================================\n";
echo "ENDPOINT 4: /Client/GetCategoryWithFare\n";
echo "================================================\n";

// Try with a common category
$testCategory = 'ECMR'; // Economy

echo "Test Parameters:\n";
echo "  pickupOffice: $testOfficeCode\n";
echo "  returnOffice: $testOfficeCode\n";
echo "  category: $testCategory\n";
echo "  startDate: $startDate\n";
echo "  endDate: $endDate\n";
echo "  customerCode: Z11338\n";
echo "  idioma: en\n\n";

$categoryParams = [
    'pickupOffice' => $testOfficeCode,
    'returnOffice' => $testOfficeCode,
    'category' => $testCategory,
    'startDate' => $startDate,
    'endDate' => $endDate,
    'customerCode' => 'Z11338',
    'idioma' => 'en'
];

$categoryResponse = makeRequest('GET', '/Client/GetCategoryWithFare', $categoryParams, $token);

echo "HTTP Code: {$categoryResponse['code']}\n";
echo "Response Length: " . strlen($categoryResponse['body']) . " bytes\n\n";

$categoryData = json_decode($categoryResponse['body'], true);
echo "Response:\n";
echo formatJson($categoryResponse['body']) . "\n\n";

// Save full category response
file_put_contents(__DIR__ . '/adobe_category_response.json', formatJson($categoryResponse['body']));
echo "✓ Full category response saved to: adobe_category_response.json\n\n";

// ============================================================================
// ENDPOINT 5: /Booking (POST) - Create Mock Booking
// ============================================================================
echo "================================================\n";
echo "ENDPOINT 5: /Booking (POST) - Create Booking\n";
echo "================================================\n";

$bookingData = [
    'pickupOffice' => $testOfficeCode,
    'returnOffice' => $testOfficeCode,
    'pickupDate' => $startDate,
    'returnDate' => $endDate,
    'category' => 'ECMR',
    'customerCode' => 'Z11338',
    'customerName' => 'Test Customer',
    'flightNumber' => '',
    'comment' => 'API Test Booking'
];

echo "Booking Data:\n";
echo formatJson(json_encode($bookingData)) . "\n\n";

$bookingResponse = makeRequest('POST', '/Booking', $bookingData, $token);

echo "HTTP Code: {$bookingResponse['code']}\n";
echo "Response:\n";
echo formatJson($bookingResponse['body']) . "\n\n";

$bookingResult = json_decode($bookingResponse['body'], true);

// Save full booking response
file_put_contents(__DIR__ . '/adobe_booking_response.json', formatJson($bookingResponse['body']));
echo "✓ Full booking response saved to: adobe_booking_response.json\n\n";

// ============================================================================
// ENDPOINT 6: /Booking (GET) - Get Booking Details
// ============================================================================
if (isset($bookingResult['data']['bookingNumber']) || isset($bookingResult['bookingNumber'])) {
    $bookingNumber = $bookingResult['data']['bookingNumber'] ?? $bookingResult['bookingNumber'] ?? null;

    if ($bookingNumber) {
        echo "================================================\n";
        echo "ENDPOINT 6: /Booking (GET) - Get Booking Details\n";
        echo "================================================\n";
        echo "Booking Number: $bookingNumber\n\n";

        $detailsParams = [
            'bookingNumber' => $bookingNumber,
            'customerCode' => 'Z11338'
        ];

        $detailsResponse = makeRequest('GET', '/Booking', $detailsParams, $token);

        echo "HTTP Code: {$detailsResponse['code']}\n";
        echo "Response:\n";
        echo formatJson($detailsResponse['body']) . "\n\n";

        // Save full details response
        file_put_contents(__DIR__ . '/adobe_booking_details_response.json', formatJson($detailsResponse['body']));
        echo "✓ Full booking details saved to: adobe_booking_details_response.json\n\n";
    }
}

echo "\n================================================\n";
echo "=== ALL ENDPOINTS TESTED ===\n";
echo "================================================\n";
echo "\nOutput files created:\n";
echo "  - adobe_offices_response.json\n";
echo "  - adobe_availability_response.json\n";
echo "  - adobe_category_response.json\n";
echo "  - adobe_booking_response.json\n";
echo "  - adobe_booking_details_response.json\n";
