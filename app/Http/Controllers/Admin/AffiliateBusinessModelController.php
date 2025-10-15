<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Affiliate\AffiliateBusinessModelService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Http\JsonResponse;

class AffiliateBusinessModelController extends Controller
{
    public function __construct(
        private AffiliateBusinessModelService $affiliateBusinessModelService
    ) {}

    /**
     * Display the business model configuration page
     */
    public function index()
    {
        $globalSettings = $this->affiliateBusinessModelService->getGlobalSettings();
        $businesses = $this->affiliateBusinessModelService->getAllBusinessesWithModels();

        return Inertia::render('AdminDashboardPages/BusinessModel/Index', [
            'globalSettings' => $globalSettings,
            'businesses' => $businesses,
        ]);
    }

    /**
     * Get global settings for API requests
     */
    public function getGlobalSettings(): JsonResponse
    {
        $globalSettings = $this->affiliateBusinessModelService->getGlobalSettings();

        if (!$globalSettings) {
            return response()->json([
                'discount_type' => 'percentage',
                'discount_value' => 0,
                'commission_rate' => 0,
                'payout_threshold' => 100.00,
                'max_qr_codes_per_business' => 100,
                'qr_code_validity_days' => 365,
                'session_tracking_hours' => 24,
                'allow_business_override' => true,
                'require_business_verification' => true,
                'auto_approve_commissions' => true,
            ]);
        }

        return response()->json([
            'discount_type' => $globalSettings->global_discount_type,
            'discount_value' => $globalSettings->global_discount_value,
            'min_booking_amount' => $globalSettings->global_min_booking_amount,
            'max_discount_amount' => $globalSettings->global_max_discount_amount,
            'commission_rate' => $globalSettings->global_commission_rate,
            'commission_type' => $globalSettings->global_commission_type,
            'payout_threshold' => $globalSettings->global_payout_threshold,
            'max_qr_codes_per_business' => $globalSettings->max_qr_codes_per_business,
            'qr_code_validity_days' => $globalSettings->qr_code_validity_days,
            'session_tracking_hours' => $globalSettings->session_tracking_hours,
            'allow_business_override' => $globalSettings->allow_business_override,
            'require_business_verification' => $globalSettings->require_business_verification,
            'auto_approve_commissions' => $globalSettings->auto_approve_commissions,
        ]);
    }

    /**
     * Update global settings
     */
    public function updateGlobalSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'discount_type' => 'required|in:percentage,fixed_amount',
            'discount_value' => 'required|numeric|min:0',
            'min_booking_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'commission_type' => 'required|in:percentage,fixed,tiered',
            'payout_threshold' => 'required|numeric|min:0',
            'max_qr_codes_per_business' => 'nullable|integer|min:1',
            'qr_code_validity_days' => 'nullable|integer|min:1',
            'session_tracking_hours' => 'nullable|integer|min:1|max:168',
            'allow_business_override' => 'boolean',
            'require_business_verification' => 'boolean',
            'auto_approve_commissions' => 'boolean',
        ]);

        try {
            $globalSettings = $this->affiliateBusinessModelService->updateGlobalSettings($data);

            return response()->json([
                'success' => true,
                'message' => 'Global settings updated successfully',
                'data' => $globalSettings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating global settings: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get businesses with their models for API requests
     */
    public function getBusinesses(): JsonResponse
    {
        $businesses = $this->affiliateBusinessModelService->getAllBusinessesWithModels();

        $formattedBusinesses = $businesses->map(function ($business) {
            return [
                'id' => $business->id,
                'uuid' => $business->uuid,
                'name' => $business->name,
                'business_type' => $business->business_type,
                'contact_email' => $business->contact_email,
                'status' => $business->status,
                'verification_status' => $business->verification_status,
                'business_model' => $business->businessModel ? [
                    'discount_type' => $business->businessModel->discount_type,
                    'discount_value' => $business->businessModel->discount_value,
                    'min_booking_amount' => $business->businessModel->min_booking_amount,
                    'max_discount_amount' => $business->businessModel->max_discount_amount,
                    'commission_rate' => $business->businessModel->commission_rate,
                    'commission_type' => $business->businessModel->commission_type,
                    'payout_threshold' => $business->businessModel->payout_threshold,
                    'max_qr_codes_per_month' => $business->businessModel->max_qr_codes_per_month,
                    'qr_code_validity_days' => $business->businessModel->qr_code_validity_days,
                ] : null,
            ];
        });

        return response()->json([
            'data' => $formattedBusinesses,
        ]);
    }

    /**
     * Update business-specific model
     */
    public function updateBusinessModel(Request $request, $businessId): JsonResponse
    {
        $data = $request->validate([
            'discount_type' => 'nullable|in:percentage,fixed_amount',
            'discount_value' => 'nullable|numeric|min:0',
            'min_booking_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'commission_type' => 'nullable|in:percentage,fixed,tiered',
            'payout_threshold' => 'nullable|numeric|min:0',
            'max_qr_codes_per_month' => 'nullable|integer|min:1',
            'qr_code_validity_days' => 'nullable|integer|min:1',
        ]);

        // Check if at least one field is being updated
        if (empty(array_filter($data, function ($value) {
            return $value !== null;
        }))) {
            return response()->json([
                'success' => false,
                'message' => 'At least one field must be provided to update the business model',
            ], 422);
        }

        try {
            $business = \App\Models\Affiliate\AffiliateBusiness::findOrFail($businessId);
            $businessModel = $this->affiliateBusinessModelService->updateBusinessModel($business, $data);

            return response()->json([
                'success' => true,
                'message' => 'Business model updated successfully',
                'data' => $businessModel,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating business model: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get business statistics
     */
    public function getBusinessStatistics(): JsonResponse
    {
        try {
            $businesses = \App\Models\Affiliate\AffiliateBusiness::with(['businessModel', 'qrCodes', 'commissions'])
                ->get();

            $stats = [
                'total_businesses' => $businesses->count(),
                'active_businesses' => $businesses->where('status', 'active')->count(),
                'pending_businesses' => $businesses->where('status', 'pending')->count(),
                'verified_businesses' => $businesses->where('verification_status', 'verified')->count(),
                'total_qr_codes' => $businesses->sum(function ($business) {
                    return $business->qrCodes->count();
                }),
                'total_commissions' => $businesses->sum(function ($business) {
                    return $business->commissions->where('status', 'paid')->sum('commission_amount');
                }),
                'businesses_with_custom_models' => $businesses->filter(function ($business) {
                    return $business->businessModel !== null;
                })->count(),
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching business statistics: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete business-specific model (reset to global)
     */
    public function deleteBusinessModel($businessId): JsonResponse
    {
        try {
            $business = \App\Models\Affiliate\AffiliateBusiness::findOrFail($businessId);

            if ($business->businessModel) {
                $business->businessModel->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Business model reset to global settings successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting business model: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Preview effective rates for a business
     */
    public function previewBusinessRates($businessId): JsonResponse
    {
        try {
            $business = \App\Models\Affiliate\AffiliateBusiness::findOrFail($businessId);
            $businessModel = $this->affiliateBusinessModelService->getBusinessModel($business);

            return response()->json([
                'success' => true,
                'data' => [
                    'business_name' => $business->name,
                    'effective_rates' => $businessModel,
                    'has_custom_model' => $business->businessModel !== null,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error previewing business rates: ' . $e->getMessage(),
            ], 500);
        }
    }
}