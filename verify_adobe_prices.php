<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Services\AdobeCarService;
$service = app(AdobeCarService::class);

$params = [
    'pickupOffice' => 'OCO',
    'returnOffice' => 'OCO',
    'startDate' => '2026-03-11 10:00',
    'endDate' => '2026-03-20 10:00',
    'customerCode' => $service->getCustomerCode()
];

echo "Fetching vehicles...\n";
$result = $service->getAvailableVehicles($params);

if (!empty($result['data']) && is_array($result['data'])) {
    // Look for a specific car if possible, or just the first one
    $car = $result['data'][0];
    echo "--- API TRUTH DATA ---\n";
    echo "Car: " . ($car['brand'] ?? '') . " " . ($car['model'] ?? '') . "\n";
    echo "Category: " . ($car['category'] ?? '') . "\n";
    echo "Days: 9\n";
    
    $tdr = floatval($car['tdr'] ?? 0);
    echo "TDR (Total Base): $tdr\n";
    
    $pli = floatval($car['pli'] ?? 0);
    echo "PLI (Total): $pli\n";
    
    $ldw = floatval($car['ldw'] ?? 0);
    echo "LDW (Total): $ldw\n";
    
    $spp = floatval($car['spp'] ?? 0);
    echo "SPP (Total): $spp\n";
    
    echo "Extras (Total Prices):\n";
    if (!empty($car['availExtras'])) {
        foreach ($car['availExtras'] as $extra) {
            echo "----------- EXTRA ITEM -----------\n";
            print_r($extra);
            echo "----------------------------------\n";
        }
    }
} else {
    echo "No vehicles found.\n";
}
