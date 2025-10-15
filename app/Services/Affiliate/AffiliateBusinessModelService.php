<?php

namespace App\Services\Affiliate;

use App\Models\Affiliate\AffiliateBusiness;
use App\Models\Affiliate\AffiliateBusinessModel;
use App\Models\Affiliate\AffiliateGlobalSetting;

class AffiliateBusinessModelService
{
    /**
     * Get the effective business model for a business (business-specific or global fallback)
     */
    public function getBusinessModel(AffiliateBusiness $business): array
    {
        // Get business-specific configuration
        $businessModel = $business->businessModel;

        // Get global settings
        $globalSettings = AffiliateGlobalSetting::first();

        if (!$globalSettings) {
            // Create default global settings if none exist
            $globalSettings = $this->createDefaultGlobalSettings();
        }

        // Use business-specific rates if configured, otherwise use global rates
        return [
            'discount_type' => $businessModel?->discount_type ?? $globalSettings->global_discount_type,
            'discount_value' => $businessModel?->discount_value ?? $globalSettings->global_discount_value,
            'min_booking_amount' => $businessModel?->min_booking_amount ?? $globalSettings->global_min_booking_amount,
            'max_discount_amount' => $businessModel?->max_discount_amount ?? $globalSettings->global_max_discount_amount,
            'commission_rate' => $businessModel?->commission_rate ?? $globalSettings->global_commission_rate,
            'commission_type' => $businessModel?->commission_type ?? $globalSettings->global_commission_type,
            'payout_threshold' => $businessModel?->payout_threshold ?? $globalSettings->global_payout_threshold,
            'max_qr_codes_per_month' => $businessModel?->max_qr_codes_per_month ?? $globalSettings->max_qr_codes_per_business,
            'qr_code_validity_days' => $businessModel?->qr_code_validity_days ?? $globalSettings->qr_code_validity_days,
        ];
    }

    /**
     * Calculate discount amount based on business model
     */
    public function calculateDiscount(array $businessModel, float $bookingAmount): float
    {
        $discountValue = $businessModel['discount_value'];
        $discountType = $businessModel['discount_type'];
        $minBookingAmount = $businessModel['min_booking_amount'];
        $maxDiscountAmount = $businessModel['max_discount_amount'];

        // Check minimum booking amount requirement
        if ($minBookingAmount && $bookingAmount < $minBookingAmount) {
            return 0;
        }

        $discountAmount = 0;

        switch ($discountType) {
            case 'percentage':
                $discountAmount = ($bookingAmount * $discountValue) / 100;
                break;
            case 'fixed_amount':
                $discountAmount = min($discountValue, $bookingAmount);
                break;
        }

        // Apply maximum discount limit if set
        if ($maxDiscountAmount && $discountAmount > $maxDiscountAmount) {
            $discountAmount = $maxDiscountAmount;
        }

        return round($discountAmount, 2);
    }

    /**
     * Calculate commission amount based on business model
     */
    public function calculateCommission(array $businessModel, float $bookingAmount, float $discountAmount = 0): float
    {
        $commissionRate = $businessModel['commission_rate'];
        $commissionType = $businessModel['commission_type'];

        $commissionableAmount = $bookingAmount - $discountAmount;

        switch ($commissionType) {
            case 'percentage':
                return round(($commissionableAmount * $commissionRate) / 100, 2);
            case 'fixed':
                return round($commissionRate, 2);
            case 'tiered':
                return $this->calculateTieredCommission($businessModel, $commissionableAmount);
            default:
                return 0;
        }
    }

    /**
     * Calculate tiered commission (basic implementation)
     */
    private function calculateTieredCommission(array $businessModel, float $amount): float
    {
        // Basic tiered commission logic
        // Can be enhanced to support custom tiers from business model
        if ($amount <= 100) {
            return round($amount * 0.05, 2); // 5%
        } elseif ($amount <= 500) {
            return round($amount * 0.07, 2); // 7%
        } else {
            return round($amount * 0.10, 2); // 10%
        }
    }

    /**
     * Create default global settings
     */
    private function createDefaultGlobalSettings(): AffiliateGlobalSetting
    {
        return AffiliateGlobalSetting::create([
            'uuid' => \Str::uuid(),
            'global_discount_type' => 'percentage',
            'global_discount_value' => 0.00,
            'global_commission_rate' => 0.00,
            'global_commission_type' => 'percentage',
            'global_payout_threshold' => 100.00,
            'max_qr_codes_per_business' => 100,
            'qr_code_validity_days' => 365,
            'session_tracking_hours' => 24,
            'allow_business_override' => true,
            'require_business_verification' => true,
            'auto_approve_commissions' => true,
            'configured_by' => auth()->id() ?? 1,
        ]);
    }

    /**
     * Update global settings
     */
    public function updateGlobalSettings(array $data): AffiliateGlobalSetting
    {
        $globalSettings = AffiliateGlobalSetting::first();

        if (!$globalSettings) {
            $globalSettings = $this->createDefaultGlobalSettings();
        }

        $globalSettings->update([
            'global_discount_type' => $data['discount_type'] ?? $globalSettings->global_discount_type,
            'global_discount_value' => $data['discount_value'] ?? $globalSettings->global_discount_value,
            'global_min_booking_amount' => $data['min_booking_amount'] ?? $globalSettings->global_min_booking_amount,
            'global_max_discount_amount' => $data['max_discount_amount'] ?? $globalSettings->global_max_discount_amount,
            'global_commission_rate' => $data['commission_rate'] ?? $globalSettings->global_commission_rate,
            'global_commission_type' => $data['commission_type'] ?? $globalSettings->global_commission_type,
            'global_payout_threshold' => $data['payout_threshold'] ?? $globalSettings->global_payout_threshold,
            'max_qr_codes_per_business' => $data['max_qr_codes_per_business'] ?? $globalSettings->max_qr_codes_per_business,
            'qr_code_validity_days' => $data['qr_code_validity_days'] ?? $globalSettings->qr_code_validity_days,
            'session_tracking_hours' => $data['session_tracking_hours'] ?? $globalSettings->session_tracking_hours,
            'allow_business_override' => $data['allow_business_override'] ?? $globalSettings->allow_business_override,
            'require_business_verification' => $data['require_business_verification'] ?? $globalSettings->require_business_verification,
            'auto_approve_commissions' => $data['auto_approve_commissions'] ?? $globalSettings->auto_approve_commissions,
            'configured_by' => auth()->id(),
        ]);

        return $globalSettings->fresh();
    }

    /**
     * Update business-specific model
     */
    public function updateBusinessModel(AffiliateBusiness $business, array $data): AffiliateBusinessModel
    {
        $businessModel = $business->businessModel ?? new AffiliateBusinessModel();

        $businessModel->business_id = $business->id;
        $businessModel->discount_type = $data['discount_type'] ?? null;
        $businessModel->discount_value = $data['discount_value'] ?? null;
        $businessModel->min_booking_amount = $data['min_booking_amount'] ?? null;
        $businessModel->max_discount_amount = $data['max_discount_amount'] ?? null;
        $businessModel->commission_rate = $data['commission_rate'] ?? null;
        $businessModel->commission_type = $data['commission_type'] ?? null;
        $businessModel->payout_threshold = $data['payout_threshold'] ?? null;
        $businessModel->max_qr_codes_per_month = $data['max_qr_codes_per_month'] ?? null;
        $businessModel->qr_code_validity_days = $data['qr_code_validity_days'] ?? null;
        $businessModel->configured_by = auth()->id();
        $businessModel->configured_at = now();

        $businessModel->save();

        return $businessModel;
    }

    /**
     * Get global settings for admin display
     */
    public function getGlobalSettings(): ?AffiliateGlobalSetting
    {
        return AffiliateGlobalSetting::first();
    }

    /**
     * Get all businesses with their models
     */
    public function getAllBusinessesWithModels()
    {
        return AffiliateBusiness::with('businessModel')->get();
    }

    /**
     * Validate business model data
     */
    public function validateBusinessModelData(array $data): array
    {
        $errors = [];

        if (isset($data['discount_value']) && $data['discount_value'] < 0) {
            $errors['discount_value'] = 'Discount value must be positive';
        }

        if (isset($data['commission_rate']) && ($data['commission_rate'] < 0 || $data['commission_rate'] > 100)) {
            $errors['commission_rate'] = 'Commission rate must be between 0 and 100';
        }

        if (isset($data['min_booking_amount']) && $data['min_booking_amount'] < 0) {
            $errors['min_booking_amount'] = 'Minimum booking amount must be positive';
        }

        if (isset($data['max_discount_amount']) && $data['max_discount_amount'] < 0) {
            $errors['max_discount_amount'] = 'Maximum discount amount must be positive';
        }

        return $errors;
    }
}