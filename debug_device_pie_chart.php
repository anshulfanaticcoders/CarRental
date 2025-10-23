<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

// Bootstrap the application
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DEVICE USAGE DISTRIBUTION PIE CHART DEBUG ===\n\n";

try {
    echo "1. Testing API call to get device stats:\n";

    // Test the actual API endpoint
    $controller = new \App\Http\Controllers\Admin\AffiliateBusinessModelController(
        app(\App\Services\Affiliate\AffiliateBusinessModelService::class)
    );

    $request = \Illuminate\Http\Request::create('/admin/affiliate/qr-analytics-data', 'GET', [
        'date_range' => '30d'
    ]);

    $response = $controller->getQrAnalyticsData($request);
    $data = json_decode($response->getContent(), true);

    echo "✅ API Response Status: " . $response->getStatusCode() . "\n\n";

    if ($response->getStatusCode() === 200) {
        echo "2. Device stats from API:\n";
        $deviceStats = $data['device_stats'] ?? [];
        echo "   Device stats array: " . json_encode($deviceStats, JSON_PRETTY_PRINT) . "\n\n";

        echo "3. Expected pie chart data structure:\n";
        echo "   Labels: ['Mobile', 'Desktop', 'Tablet', 'Other']\n";
        echo "   Data should be: [mobile_count, desktop_count, tablet_count, other_count]\n\n";

        echo "4. Actual data mapping:\n";
        $expectedData = [
            $deviceStats['mobile'] ?? 0,
            $deviceStats['desktop'] ?? 0,
            $deviceStats['tablet'] ?? 0,
            $deviceStats['other'] ?? 0
        ];

        echo "   Mobile: " . $expectedData[0] . " (from device_stats.mobile: " . ($deviceStats['mobile'] ?? 'NULL') . ")\n";
        echo "   Desktop: " . $expectedData[1] . " (from device_stats.desktop: " . ($deviceStats['desktop'] ?? 'NULL') . ")\n";
        echo "   Tablet: " . $expectedData[2] . " (from device_stats.tablet: " . ($deviceStats['tablet'] ?? 'NULL') . ")\n";
        echo "   Other: " . $expectedData[3] . " (from device_stats.other: " . ($deviceStats['other'] ?? 'NULL') . ")\n\n";

        echo "5. Raw database verification:\n";
        $rawDeviceData = \App\Models\Affiliate\AffiliateCustomerScan::selectRaw('device_type, COUNT(*) as count')
            ->groupBy('device_type')
            ->pluck('count', 'device_type')
            ->toArray();

        echo "   Raw database device counts:\n";
        foreach ($rawDeviceData as $device => $count) {
            echo "     $device: $count\n";
        }

        echo "\n6. Pie chart total verification:\n";
        $totalDevices = array_sum($expectedData);
        echo "   Total devices in pie chart: $totalDevices\n";
        echo "   Total scans in database: " . \App\Models\Affiliate\AffiliateCustomerScan::count() . "\n";

        if ($totalDevices === 0) {
            echo "   ⚠️  WARNING: Total devices is 0! Pie chart will show empty data.\n";
        }

        echo "\n7. Vue component expected data:\n";
        echo "   deviceUsageChart.value should equal:\n";
        echo "   {\n";
        echo "     labels: ['Mobile', 'Desktop', 'Tablet', 'Other'],\n";
        echo "     data: [" . implode(', ', $expectedData) . "]\n";
        echo "   }\n\n";

    } else {
        echo "❌ API Error: " . $response->getContent() . "\n";
    }

} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n=== PIE CHART DEBUG COMPLETE ===\n";