<?php

require_once 'vendor/autoload.php';

use App\Services\AdobeCarService;
use Illuminate\Support\Facades\Log;

// Test Adobe API GetCategoryWithFare
$adobeService = new AdobeCarService();

// Test with the same parameters from the logs
$locationCode = 'HFT';
$category = 'p';
$dates = [
    'startdate' => '2025-11-20 10:00',
    'enddate' => '2025-11-21 10:00'
];

echo "Testing Adobe API GetCategoryWithFare...\n";
echo "Location: {$locationCode}\n";
echo "Category: {$category}\n";
echo "Dates: " . json_encode($dates) . "\n\n";

// Call the API
$result = $adobeService->getProtectionsAndExtras($locationCode, $category, $dates);

echo "API Response:\n";
echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

// Log to Laravel log file
Log::info('Manual Adobe API Test', [
    'result' => $result,
    'protections_count' => isset($result['protections']) ? count($result['protections']) : 0,
    'extras_count' => isset($result['extras']) ? count($result['extras']) : 0
]);

echo "Check Laravel log file for detailed response data.\n";