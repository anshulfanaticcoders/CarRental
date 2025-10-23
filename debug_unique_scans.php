<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

// Bootstrap the application
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== UNIQUE SCANS DEBUGGING ===\n\n";

try {
    echo "1. Direct database count of unique customer scans:\n";

    // Check the same query as the overview card
    $uniqueScansCount = \App\Models\Affiliate\AffiliateCustomerScan::where('created_at', '>=', now()->subDays(30))
        ->whereNotNull('customer_id')
        ->distinct('customer_id')
        ->count('customer_id');

    echo "   Overview calculation: $uniqueScansCount unique scans\n\n";

    echo "2. Raw affiliate_customer_scans data:\n";
    $allScans = \App\Models\Affiliate\AffiliateCustomerScan::where('created_at', '>=', now()->subDays(30))
        ->select('customer_id', 'created_at', 'qr_code_id')
        ->get();

    echo "   Total scans in period: " . $allScans->count() . "\n";
    echo "   Scans with customer_id: " . $allScans->whereNotNull('customer_id')->count() . "\n";
    echo "   Scans without customer_id: " . $allScans->whereNull('customer_id')->count() . "\n\n";

    echo "3. Unique customer IDs found:\n";
    $uniqueCustomers = \App\Models\Affiliate\AffiliateCustomerScan::where('created_at', '>=', now()->subDays(30))
        ->whereNotNull('customer_id')
        ->distinct('customer_id')
        ->pluck('customer_id');

    echo "   Unique customer IDs: " . json_encode($uniqueCustomers->toArray()) . "\n";
    echo "   Count: " . $uniqueCustomers->count() . "\n\n";

    echo "4. QR Code unique_scans field values:\n";
    $qrCodes = \App\Models\Affiliate\AffiliateQrCode::with(['business'])->get();

    foreach ($qrCodes as $qr) {
        echo "   QR {$qr->id} ({$qr->qr_code_value}): unique_scans = {$qr->unique_scans}, total_scans = {$qr->total_scans}\n";
    }

    $totalUniqueScans = $qrCodes->sum('unique_scans');
    $totalScans = $qrCodes->sum('total_scans');

    echo "   QR codes total unique_scans: $totalUniqueScans\n";
    echo "   QR codes total total_scans: $totalScans\n\n";

    echo "5. Comparison:\n";
    echo "   Overview calculation: $uniqueScansCount\n";
    echo "   QR codes sum: $totalUniqueScans\n";
    echo "   Difference: " . abs($uniqueScansCount - $totalUniqueScans) . "\n\n";

    if ($uniqueScansCount != $totalUniqueScans) {
        echo "   ⚠️  DISCREPANCY FOUND!\n";
        echo "   The overview card and QR codes are showing different values!\n";
    } else {
        echo "   ✅ Values match\n";
    }

} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n=== DEBUGGING COMPLETE ===\n";