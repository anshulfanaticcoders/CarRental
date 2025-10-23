<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

// Bootstrap the application
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== RAW DATA VERIFICATION ===\n\n";

try {
    echo "1. Raw Customer Scans Data (last 30 days):\n";
    $startDate = now()->subDays(30);

    // Get raw customer scans
    $rawScans = \App\Models\Affiliate\AffiliateCustomerScan::where('created_at', '>=', $startDate)
        ->select('device_type', 'customer_id', 'qr_code_id', 'booking_completed', 'booking_id', 'created_at')
        ->get();

    echo "   Total scans: " . $rawScans->count() . "\n";
    echo "   Scans with customer_id: " . $rawScans->whereNotNull('customer_id')->count() . "\n";
    echo "   Scans without customer_id: " . $rawScans->whereNull('customer_id')->count() . "\n";
    echo "   Booking completed scans: " . $rawScans->where('booking_completed', true)->count() . "\n\n";

    echo "2. Device Breakdown:\n";
    $deviceBreakdown = $rawScans->groupBy('device_type');
    foreach ($deviceBreakdown as $device => $scans) {
        echo "   $device: " . $scans->count() . " scans\n";
    }
    echo "\n";

    echo "3. Location Breakdown (via QR codes):\n";
    // Get location info through QR codes
    $locationData = \App\Models\Affiliate\AffiliateCustomerScan::where('affiliate_customer_scans.created_at', '>=', $startDate)
        ->leftJoin('affiliate_qr_codes', 'affiliate_customer_scans.qr_code_id', '=', 'affiliate_qr_codes.id')
        ->leftJoin('affiliate_businesses', 'affiliate_qr_codes.business_id', '=', 'affiliate_businesses.id')
        ->selectRaw("
            affiliate_businesses.city as location,
            affiliate_businesses.country as country,
            COUNT(*) as scans,
            COUNT(DISTINCT CASE WHEN affiliate_customer_scans.customer_id IS NOT NULL THEN affiliate_customer_scans.customer_id END) as unique_scans
        ")
        ->groupBy('location', 'country')
        ->orderByDesc('scans')
        ->get();

    foreach ($locationData as $location) {
        echo "   {$location->location}, {$location->country}: {$location->scans} scans, {$location->unique_scans} unique\n";
    }
    echo "\n";

    echo "4. QR Code Performance:\n";
    // Get QR code performance
    $qrPerformance = \App\Models\Affiliate\AffiliateCustomerScan::where('affiliate_customer_scans.created_at', '>=', $startDate)
        ->leftJoin('affiliate_qr_codes', 'affiliate_customer_scans.qr_code_id', '=', 'affiliate_qr_codes.id')
        ->leftJoin('affiliate_businesses', 'affiliate_qr_codes.business_id', '=', 'affiliate_businesses.id')
        ->selectRaw("
            affiliate_qr_codes.id as qr_id,
            affiliate_qr_codes.qr_code_value,
            affiliate_businesses.name as business_name,
            COUNT(*) as total_scans,
            COUNT(DISTINCT CASE WHEN affiliate_customer_scans.customer_id IS NOT NULL THEN affiliate_customer_scans.customer_id END) as unique_scans,
            SUM(CASE WHEN affiliate_customer_scans.booking_completed = true THEN 1 ELSE 0 END) as conversions
        ")
        ->groupBy('qr_id', 'qr_code_value', 'business_name')
        ->orderByDesc('total_scans')
        ->take(5)
        ->get();

    foreach ($qrPerformance as $qr) {
        $conversionRate = $qr->total_scans > 0 ? ($qr->conversions / $qr->total_scans) * 100 : 0;
        echo "   QR {$qr->qr_code_value} ({$qr->business_name}): {$qr->total_scans} scans, {$qr->unique_scans} unique, {$qr->conversions} conv, {$conversionRate}% rate\n";
    }

    echo "\n=== VERIFICATION SUMMARY ===\n";
    echo "Total Scans: " . $rawScans->count() . "\n";
    echo "Unique Customers: " . $rawScans->whereNotNull('customer_id')->unique('customer_id')->count() . "\n";
    echo "Conversions: " . $rawScans->where('booking_completed', true)->count() . "\n";

} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n=== CHECK COMPLETE ===\n";