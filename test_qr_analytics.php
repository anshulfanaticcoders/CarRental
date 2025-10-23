<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

// Bootstrap the application
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TESTING QR ANALYTICS FIX ===\n";

try {
    $controller = new \App\Http\Controllers\Admin\AffiliateBusinessModelController(
        app(\App\Services\Affiliate\AffiliateBusinessModelService::class)
    );

    // Create a request like the frontend would
    $request = \Illuminate\Http\Request::create('/admin/affiliate/qr-analytics-data', 'GET', [
        'date_range' => '30d'
    ]);

    echo "Request date_range: " . $request->get('date_range', '30d') . "\n";

    // Call the method
    $response = $controller->getQrAnalyticsData($request);

    echo "Response status: " . $response->getStatusCode() . "\n";

    if ($response->getStatusCode() === 200) {
        $data = json_decode($response->getContent(), true);
        echo "QR Codes in response: " . ($data['overview']['total_qr_codes'] ?? 'null') . "\n";
        echo "Total scans: " . ($data['overview']['total_scans'] ?? 'null') . "\n";
        echo "Total QR codes in database: " . \App\Models\Affiliate\AffiliateQrCode::count() . "\n";
    } else {
        echo "Error response: " . $response->getContent() . "\n";
    }

} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}