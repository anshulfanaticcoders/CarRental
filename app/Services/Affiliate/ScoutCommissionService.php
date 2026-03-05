<?php

namespace App\Services\Affiliate;

use App\Models\Affiliate\AffiliateBusiness;
use App\Models\Affiliate\AffiliateCommission;
use App\Models\Affiliate\AffiliateCustomerScan;
use App\Models\Affiliate\AffiliateGlobalSetting;
use App\Models\Affiliate\AffiliateQrCode;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ScoutCommissionService
{
    /**
     * Create a commission record for an affiliate referral.
     *
     * Always uses global_commission_rate (default 3%) on BASE PRICE (vendor price before markup).
     * Wrapped in try/catch so it never breaks the booking flow.
     *
     * @param int    $bookingId    The booking ID
     * @param int|null $customerId The customer user ID
     * @param float  $basePrice    Vendor/provider base price (before platform markup)
     * @param string $bookingType  e.g. 'stripe', 'greenmotion', 'okmobility'
     * @param array  $affiliateData Session affiliate_data array
     * @param string $bookingCurrency The currency of the booking
     */
    public function createCommission(
        int $bookingId,
        ?int $customerId,
        float $basePrice,
        string $bookingType,
        array $affiliateData,
        string $bookingCurrency = 'EUR'
    ): void {
        try {
            if (empty($affiliateData['customer_scan_id'])) {
                return;
            }

            $customerScan = AffiliateCustomerScan::find($affiliateData['customer_scan_id']);
            if (!$customerScan) {
                Log::warning('ScoutCommissionService: Customer scan not found', [
                    'customer_scan_id' => $affiliateData['customer_scan_id'],
                ]);
                return;
            }

            $qrCode = AffiliateQrCode::find($customerScan->qr_code_id);
            if (!$qrCode || !$qrCode->business) {
                Log::warning('ScoutCommissionService: QR code or business not found', [
                    'qr_code_id' => $customerScan->qr_code_id ?? null,
                ]);
                return;
            }

            $business = $qrCode->business;

            // Get global commission rate (default 3%)
            $globalSettings = AffiliateGlobalSetting::first();
            $commissionRate = $globalSettings->global_commission_rate ?? 3.0;

            // Commission = rate% of base price (vendor price, before any markup)
            $commissionAmount = round(($basePrice * $commissionRate) / 100, 2);

            // Update customer scan
            $customerScan->update([
                'booking_completed' => true,
                'booking_id' => $bookingId,
                'booking_type' => $bookingType,
                'conversion_time_minutes' => $customerScan->created_at->diffInMinutes(now()),
                'discount_applied' => 0,
                'discount_percentage' => 0,
            ]);

            // Update QR code conversion tracking
            $qrCode->update([
                'conversion_count' => $qrCode->conversion_count + 1,
                'total_revenue_generated' => $qrCode->total_revenue_generated + $basePrice,
                'last_scanned_at' => now(),
            ]);

            // Create commission record
            AffiliateCommission::create([
                'uuid' => Str::uuid(),
                'business_id' => $business->id,
                'location_id' => $qrCode->location_id,
                'booking_id' => $bookingId,
                'customer_id' => $customerId,
                'qr_scan_id' => $customerScan->id,
                'booking_amount' => $basePrice,
                'commissionable_amount' => $basePrice,
                'commission_amount' => $commissionAmount,
                'discount_amount' => 0,
                'commission_rate' => $commissionRate,
                'commission_type' => 'percentage',
                'status' => 'pending',
                'booking_type' => $bookingType,
                'net_commission' => $commissionAmount,
                'tax_amount' => 0,
            ]);

            Log::info('ScoutCommissionService: Commission created', [
                'booking_id' => $bookingId,
                'business_id' => $business->id,
                'base_price' => $basePrice,
                'commission_rate' => $commissionRate,
                'commission_amount' => $commissionAmount,
                'booking_type' => $bookingType,
            ]);
        } catch (\Exception $e) {
            // Never break the booking flow
            Log::error('ScoutCommissionService: Failed to create commission', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
