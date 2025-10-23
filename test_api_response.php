<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

// Bootstrap the application
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== API RESPONSE TEST ===\n\n";

try {
    // Test the actual API endpoint
    $controller = new \App\Http\Controllers\Admin\AffiliateBusinessModelController(
        app(\App\Services\Affiliate\AffiliateBusinessModelService::class)
    );

    $request = \Illuminate\Http\Request::create('/admin/affiliate/qr-analytics-data', 'GET', [
        'date_range' => '30d'
    ]);

    $response = $controller->getQrAnalyticsData($request);

    echo "API Response Status: " . $response->getStatusCode() . "\n\n";

    if ($response->getStatusCode() === 200) {
        $data = json_decode($response->getContent(), true);

        echo "=== OVERVIEW DATA ===\n";
        echo json_encode($data['overview'], JSON_PRETTY_PRINT) . "\n\n";

        echo "=== DEVICE STATS ===\n";
        echo json_encode($data['device_stats'], JSON_PRETTY_PRINT) . "\n\n";

        echo "=== LOCATION STATS ===\n";
        echo json_encode($data['location_stats'], JSON_PRETTY_PRINT) . "\n\n";

        echo "=== TOP PERFORMERS ===\n";
        echo json_encode($data['top_performers'], JSON_PRETTY_PRINT) . "\n\n";

        echo "=== TOTAL VERIFICATION ===\n";
        $totalFromOverview = $data['overview']['total_scans'];
        $totalFromLocations = array_sum(array_column($data['location_stats'], 'scans'));
        $totalFromTopPerformers = array_sum(array_column($data['top_performers'], 'total_scans'));

        echo "Overview total_scans: $totalFromOverview\n";
        echo "Location stats sum: $totalFromLocations\n";
        echo "Top performers sum: $totalFromTopPerformers\n\n";

        if ($totalFromOverview === $totalFromLocations && $totalFromOverview === $totalFromTopPerformers) {
            echo "✅ ALL TOTALS MATCH - Data is consistent!\n";
        } else {
            echo "❌ TOTALS DON'T MATCH - Data inconsistency found!\n";
            echo "Overview shows: $totalFromOverview scans\n";
            echo "Locations sum to: $totalFromLocations scans\n";
            echo "Top performers sum to: $totalFromTopPerformers scans\n";
        }
    } else {
        echo "API ERROR: " . $response->getContent() . "\n";
    }

} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";