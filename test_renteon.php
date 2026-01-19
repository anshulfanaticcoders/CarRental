<?php

use App\Services\RenteonService;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = app(RenteonService::class);

echo "Testing Renteon API Connection...\n";

// 1. Test Locations
echo "\n--- Fetching Locations ---\n";
try {
    $locations = $service->getLocations();
    if ($locations) {
        echo "Successfully fetched " . count($locations) . " locations.\n";
        
        // Find a PickupDropoff location
        $pickupCode = null;
        foreach ($locations as $loc) {
            if (isset($loc['Category']) && $loc['Category'] === 'PickupDropoff') {
                $pickupCode = $loc['Code'] ?? $loc['LocationCode'] ?? $loc['ID'];
                echo "Found PickupDropoff Location: " . $loc['Name'] . " ($pickupCode)\n";
                break;
            }
        }
        
        if (!$pickupCode && isset($locations[0])) {
             echo "No PickupDropoff found, falling back to first location.\n";
             $pickupCode = $locations[0]['Code'] ?? $locations[0]['LocationCode'] ?? $locations[0]['ID'] ?? null;
        }

    } else {
        echo "Failed to fetch locations or no locations returned.\n";
        $pickupCode = null;
    }
} catch (\Exception $e) {
    echo "Error fetching locations: " . $e->getMessage() . "\n";
    $pickupCode = null;
}

// Ensure we have a pickupCode for testing
$testLocations = ['BG-POM']; // Focus on user's location
$pickupCode = null;

// Set unlimited execution time for CLI testing
set_time_limit(0);

echo "Testing specific location: BG-POM...\n";

foreach ($testLocations as $locCode) {
    echo "\n------------------------------------------------\n";
    echo "Testing Location: $locCode\n";
    $pickupCode = $locCode;
    
    // Set dates for a future search (e.g., 30 days from now)
    $startDate = now()->addDays(30)->format('Y-m-d');
    $startTime = '10:00';
    $endDate = now()->addDays(37)->format('Y-m-d');
    $endTime = '10:00';

    echo "Search Params: $startDate $startTime to $endDate $endTime\n";

    try {
        echo "Fetching Vehicles from ALL Providers (logging progress locally)...\n";
        // We'll rely on the service logs to see details, but let's print if we find something
        $vehicles = $service->getVehiclesFromAllProviders($pickupCode, $pickupCode, $startDate, $startTime, $endDate, $endTime);
        
        if (!empty($vehicles)) {
             echo "SUCCESS! Found " . count($vehicles) . " vehicles for location $pickupCode.\n";
             print_r($vehicles[0]);
             exit;
        } else {
            echo "No vehicles found for $pickupCode after checking all providers.\n";
        }

    } catch (\Exception $e) {
        echo "Error fetching vehicles for $pickupCode: " . $e->getMessage() . "\n";
    }
}
exit;

/*
// 2. Test Vehicles (if we have a location)
if ($pickupCode) {
    echo "\n--- Fetching Vehicles for Location: $pickupCode ---\n";
*/
    
    // Set dates for a future search (e.g., 30 days from now to ensure availability)
    $startDate = now()->addDays(30)->format('Y-m-d');
    $startTime = '10:00';
    $endDate = now()->addDays(33)->format('Y-m-d');
    $endTime = '10:00';

    echo "Search Params: $startDate $startTime to $endDate $endTime\n";

    try {
        // Try to get providers first to see what we have
        echo "Fetching Providers List...\n";
        $providers = $service->getProviders();
        if ($providers) {
            echo "Found " . count($providers) . " providers.\n";
            // print_r($providers); // Commented out to reduce noise, just count is enough for now
        } else {
            echo "No providers found or error fetching providers.\n";
        }

        echo "\nFetching Vehicles from ALL Providers (this may take a while)...\n";
        $vehicles = $service->getVehiclesFromAllProviders($pickupCode, $pickupCode, $startDate, $startTime, $endDate, $endTime);
        
        if (!empty($vehicles)) {
             echo "API Call Successful.\n";
             echo "Found " . count($vehicles) . " vehicles.\n";
             echo "First Vehicle Sample:\n";
             print_r($vehicles[0]);
        } else {
            echo "No vehicles found in the response (Empty Array).\n";
        }

    } catch (\Exception $e) {
        echo "Error fetching vehicles: " . $e->getMessage() . "\n";
    }
// } else {
//    echo "\nSkipping vehicle search because no valid location code was found.\n";
// }
