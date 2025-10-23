<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

// Bootstrap the application
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== AFFILIATE DATA SYNCHRONIZATION ===\n\n";

try {
    echo "1. Getting current QR codes statistics...\n";

    // Get all QR codes with their current statistics
    $qrCodes = \App\Models\Affiliate\AffiliateQrCode::all();

    echo "   Found " . $qrCodes->count() . " QR codes in database\n\n";

    echo "2. Calculating actual statistics from customer scans...\n";

    foreach ($qrCodes as $qrCode) {
        // Get actual scan data for this QR code
        $customerScans = \App\Models\Affiliate\AffiliateCustomerScan::where('qr_code_id', $qrCode->id)->get();

        // Calculate real statistics
        $actualTotalScans = $customerScans->count();
        $actualUniqueScans = $customerScans->whereNotNull('customer_id')->unique('customer_id')->count();
        $actualConversions = $customerScans->where('booking_completed', true)->whereNotNull('booking_id')->count();

        echo "   QR Code ID {$qrCode->id} ({$qrCode->qr_code_value}):\n";
        echo "     Current totals: scans={$qrCode->total_scans}, unique={$qrCode->unique_scans}, conversions={$qrCode->conversion_count}\n";
        echo "     Actual totals:  scans={$actualTotalScans}, unique={$actualUniqueScans}, conversions={$actualConversions}\n";

        // Check if update is needed
        if ($qrCode->total_scans != $actualTotalScans ||
            $qrCode->unique_scans != $actualUniqueScans ||
            $qrCode->conversion_count != $actualConversions) {

            echo "     ❌ MISMATCH DETECTED - Updating QR code statistics\n";

            // Update the QR code with correct statistics
            $qrCode->update([
                'total_scans' => $actualTotalScans,
                'unique_scans' => $actualUniqueScans,
                'conversion_count' => $actualConversions,
                'updated_at' => now()
            ]);

            echo "     ✅ Updated QR code {$qrCode->id}\n";
        } else {
            echo "     ✅ Statistics already correct\n";
        }
        echo "\n";
    }

    echo "3. Verifying synchronization...\n";

    // Verify final counts
    $finalTotalScans = \App\Models\Affiliate\AffiliateQrCode::sum('total_scans');
    $finalCustomerScans = \App\Models\Affiliate\AffiliateCustomerScan::count();

    echo "   Final QR codes total_scans: {$finalTotalScans}\n";
    echo "   Final customer scans count: {$finalCustomerScans}\n";

    if ($finalTotalScans == $finalCustomerScans) {
        echo "   ✅ SYNCHRONIZATION SUCCESSFUL - Counts match!\n";
    } else {
        echo "   ⚠️  Counts still don't match - manual investigation needed\n";
    }

    echo "\n4. Summary of changes:\n";

    // Show before/after comparison
    $overviewBefore = $qrCodes->sum('total_scans');
    $overviewAfter = \App\Models\Affiliate\AffiliateQrCode::sum('total_scans');

    echo "   Overview cards before: {$overviewBefore} total scans\n";
    echo "   Overview cards after: {$overviewAfter} total scans\n";
    echo "   Customer scans (actual): {$finalCustomerScans} total scans\n";

    if ($overviewAfter == $finalCustomerScans) {
        echo "   ✅ Data consistency achieved!\n";
    } else {
        echo "   ⚠️  Further investigation required\n";
    }

} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n=== SYNCHRONIZATION COMPLETE ===\n";