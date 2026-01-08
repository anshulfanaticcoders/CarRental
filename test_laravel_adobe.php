<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\AdobeCarService;

$service = app(AdobeCarService::class);

echo "Attempting Adobe booking via Laravel service...\n";

// Use dates that worked for availability in previous tests
$pickupDate = '2026-03-11 10:00';
$returnDate = '2026-03-20 10:00';

$params = [
    'pickupOffice' => 'OCO',
    'returnOffice' => 'OCO',
    'pickupDate' => $pickupDate,
    'returnDate' => $returnDate,
    'category' => 'n',
    'customerCode' => $service->getCustomerCode(),
    'customerName' => 'Test Customer',
    'flightNumber' => '',
    'comment' => 'Laravel integrated test'
];

echo "Params: " . json_encode($params, JSON_PRETTY_PRINT) . "\n";

$result = $service->createBooking($params);

echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
