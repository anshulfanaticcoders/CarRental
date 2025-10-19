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

    /**
     * Display business statistics dashboard
     */
    public function businessStatistics()
    {
        return Inertia::render('AdminDashboardPages/BusinessModel/Statistics');
    }

    /**
     * Get business statistics data for the statistics dashboard
     */
    public function getBusinessStatisticsData(Request $request): JsonResponse
    {
        try {
            // Simple, working version with basic statistics
            $totalBusinesses = \App\Models\Affiliate\AffiliateBusiness::count();
            $activeBusinesses = \App\Models\Affiliate\AffiliateBusiness::where('status', 'active')->count();
            $pendingVerification = \App\Models\Affiliate\AffiliateBusiness::where('verification_status', 'pending')->count();

            // Basic QR code stats
            $totalQrCodes = \App\Models\Affiliate\AffiliateQrCode::count();
            $totalScans = \App\Models\Affiliate\AffiliateQrCode::sum('total_scans');
            $totalRevenue = \App\Models\Affiliate\AffiliateQrCode::sum('total_revenue_generated');

            // Basic commission stats
            $totalCommissions = \App\Models\Affiliate\AffiliateCommission::where('status', 'paid')->sum('commission_amount');
            $pendingPayouts = \App\Models\Affiliate\AffiliateCommission::where('status', 'approved')->sum('commission_amount');

            // Calculate conversion rate safely
            $avgConversionRate = 0;
            if ($totalScans > 0) {
                $totalConversions = \App\Models\Affiliate\AffiliateQrCode::sum('conversion_count');
                $avgConversionRate = ($totalConversions / $totalScans) * 100;
            }

            $overview = [
                'total_businesses' => $totalBusinesses,
                'active_businesses' => $activeBusinesses,
                'pending_verification' => $pendingVerification,
                'total_qr_codes' => $totalQrCodes,
                'total_scans' => $totalScans,
                'total_revenue' => $totalRevenue,
                'total_commissions' => $totalCommissions,
                'pending_payouts' => $pendingPayouts,
                'avg_conversion_rate' => $avgConversionRate,
            ];

            // Simple recent growth
            $recentGrowth = [
                'businesses' => 0,
                'active' => 0,
                'qr_codes' => 0,
                'scans' => 0,
                'revenue' => 0,
                'commissions' => 0,
            ];

            // Simple top performers
            $topPerformers = \App\Models\Affiliate\AffiliateBusiness::take(5)->get()->map(function ($business) {
                return [
                    'id' => $business->id,
                    'name' => $business->name,
                    'business_type' => $business->business_type,
                    'total_scans' => 0,
                    'total_revenue' => 0,
                    'conversion_rate' => 0,
                ];
            });

            // Simple recent activity
            $recentActivity = [
                [
                    'id' => 1,
                    'type' => 'success',
                    'description' => 'Statistics loaded successfully',
                    'created_at' => now()->toISOString(),
                ],
                [
                    'id' => 2,
                    'type' => 'info',
                    'description' => $totalQrCodes . ' QR codes in system',
                    'created_at' => now()->subHours(2)->toISOString(),
                ],
                [
                    'id' => 3,
                    'type' => 'warning',
                    'description' => $pendingVerification . ' businesses pending verification',
                    'created_at' => now()->subHours(4)->toISOString(),
                ],
            ];

            return response()->json([
                'overview' => $overview,
                'recent_growth' => $recentGrowth,
                'top_performers' => $topPerformers,
                'recent' => $recentActivity,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching business statistics: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    // Helper methods for growth calculations
    private function calculateGrowthRate($previous, $current)
    {
        if ($previous == 0) return $current > 0 ? 100 : 0;
        return (($current - $previous) / $previous) * 100;
    }

    private function calculateQrGrowth($startDate)
    {
        $current = \App\Models\Affiliate\AffiliateQrCode::where('created_at', '>=', $startDate)->count();
        $previous = \App\Models\Affiliate\AffiliateQrCode::where('created_at', '>=', $startDate->copy()->subDays($startDate->diffInDays(now())))
            ->where('created_at', '<', $startDate)->count();

        return $this->calculateGrowthRate($previous, $current);
    }

    private function calculateScanGrowth($startDate)
    {
        $current = \App\Models\Affiliate\AffiliateQrCode::where('created_at', '>=', $startDate)->sum('total_scans');
        $previous = \App\Models\Affiliate\AffiliateQrCode::where('created_at', '>=', $startDate->copy()->subDays($startDate->diffInDays(now())))
            ->where('created_at', '<', $startDate)
            ->sum('total_scans');

        return $this->calculateGrowthRate($previous, $current);
    }

    private function calculateRevenueGrowth($startDate)
    {
        $current = \App\Models\Affiliate\AffiliateQrCode::where('created_at', '>=', $startDate)->sum('total_revenue_generated');
        $previous = \App\Models\Affiliate\AffiliateQrCode::where('created_at', '>=', $startDate->copy()->subDays($startDate->diffInDays(now())))
            ->where('created_at', '<', $startDate)
            ->sum('total_revenue_generated');

        return $this->calculateGrowthRate($previous, $current);
    }

    private function calculateCommissionGrowth($startDate)
    {
        $current = \App\Models\Affiliate\AffiliateCommission::where('created_at', '>=', $startDate)->sum('commission_amount');
        $previous = \App\Models\Affiliate\AffiliateCommission::where('created_at', '>=', $startDate->copy()->subDays($startDate->diffInDays(now())))
            ->where('created_at', '<', $startDate)->sum('commission_amount');

        return $this->calculateGrowthRate($previous, $current);
    }

    /**
     * Display business verification management page
     */
    public function businessVerification()
    {
        return Inertia::render('AdminDashboardPages/BusinessModel/Verification');
    }

    /**
     * Display payment tracking page
     */
    public function paymentTracking()
    {
        return Inertia::render('AdminDashboardPages/BusinessModel/PaymentTracking');
    }

    /**
     * Display business details page
     */
    public function businessDetails($businessId = null)
    {
        return Inertia::render('AdminDashboardPages/BusinessModel/Details', [
            'businessId' => $businessId
        ]);
    }

    /**
     * Display commission management page
     */
    public function commissionManagement()
    {
        return Inertia::render('AdminDashboardPages/BusinessModel/CommissionManagement');
    }

    /**
     * Display QR analytics page
     */
    public function qrAnalytics()
    {
        return Inertia::render('AdminDashboardPages/BusinessModel/QRAnalytics');
    }

    /**
     * Get commissions data for the commission management page
     */
    public function getCommissionsData(Request $request): JsonResponse
    {
        try {
            $query = \App\Models\Affiliate\AffiliateCommission::with(['business', 'affiliate'])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('business', function ($subQ) use ($search) {
                        $subQ->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('affiliate', function ($subQ) use ($search) {
                        $subQ->where('name', 'like', "%{$search}%")
                           ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhere('qr_code_id', 'like', "%{$search}%");
                });
            }

            // Date range filtering
            if ($request->filled('date_range')) {
                $dateRange = $request->date_range;
                $now = now();
                switch ($dateRange) {
                    case '7d':
                        $query->where('created_at', '>=', $now->copy()->subDays(7));
                        break;
                    case '30d':
                        $query->where('created_at', '>=', $now->copy()->subDays(30));
                        break;
                    case '90d':
                        $query->where('created_at', '>=', $now->copy()->subDays(90));
                        break;
                    case '1y':
                        $query->where('created_at', '>=', $now->copy()->subYear());
                        break;
                }
            }

            $perPage = $request->get('per_page', 15);
            $commissions = $query->paginate($perPage);

            $formattedCommissions = $commissions->getCollection()->map(function ($commission) {
                return [
                    'id' => $commission->id,
                    'business_name' => $commission->business->name ?? 'N/A',
                    'business_type' => $commission->business->business_type ?? 'N/A',
                    'affiliate_name' => $commission->affiliate->name ?? 'N/A',
                    'affiliate_email' => $commission->affiliate->email ?? 'N/A',
                    'qr_code_id' => $commission->qr_code_id,
                    'amount' => $commission->commission_amount,
                    'currency' => $commission->currency ?? 'EUR',
                    'status' => $commission->status,
                    'created_at' => $commission->created_at->toISOString(),
                ];
            });

            return response()->json([
                'data' => $formattedCommissions,
                'pagination' => [
                    'current_page' => $commissions->currentPage(),
                    'last_page' => $commissions->lastPage(),
                    'per_page' => $commissions->perPage(),
                    'total' => $commissions->total(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching commissions: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get commission statistics
     */
    public function getCommissionStatistics(Request $request): JsonResponse
    {
        try {
            $dateRange = $request->get('date_range', '30d');
            $now = now();
            $startDate = match ($dateRange) {
                '7d' => $now->copy()->subDays(7),
                '30d' => $now->copy()->subDays(30),
                '90d' => $now->copy()->subDays(90),
                '1y' => $now->copy()->subYear(),
                default => $now->copy()->subDays(30),
            };

            $commissions = \App\Models\Affiliate\AffiliateCommission::where('created_at', '>=', $startDate);

            $stats = [
                'overview' => [
                    'total_commissions' => $commissions->count(),
                    'pending_commissions' => $commissions->where('status', 'pending')->count(),
                    'approved_commissions' => $commissions->where('status', 'approved')->count(),
                    'paid_commissions' => $commissions->where('status', 'paid')->count(),
                    'total_amount' => $commissions->sum('commission_amount'),
                    'average_commission' => $commissions->avg('commission_amount') ?? 0,
                ],
                'top_affiliates' => $this->getTopAffiliates($startDate),
                'monthly_growth' => $this->calculateMonthlyGrowth($startDate),
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching commission statistics: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update commission status
     */
    public function updateCommissionStatus(Request $request, $commissionId): JsonResponse
    {
        try {
            $commission = \App\Models\Affiliate\AffiliateCommission::findOrFail($commissionId);

            $data = $request->validate([
                'status' => 'required|in:pending,approved,paid,rejected'
            ]);

            $commission->update(['status' => $data['status']]);

            return response()->json([
                'success' => true,
                'message' => 'Commission status updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating commission status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process commission payment
     */
    public function processCommissionPayment($commissionId): JsonResponse
    {
        try {
            $commission = \App\Models\Affiliate\AffiliateCommission::findOrFail($commissionId);

            if ($commission->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Commission must be approved before payment can be processed'
                ], 422);
            }

            $commission->update(['status' => 'paid', 'paid_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export commissions data
     */
    public function exportCommissions(Request $request)
    {
        try {
            $commissions = \App\Models\Affiliate\AffiliateCommission::with(['business', 'affiliate'])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->filled('status')) {
                $commissions->where('status', $request->status);
            }

            if ($request->filled('date_range')) {
                $dateRange = $request->date_range;
                $now = now();
                switch ($dateRange) {
                    case '7d':
                        $commissions->where('created_at', '>=', $now->copy()->subDays(7));
                        break;
                    case '30d':
                        $commissions->where('created_at', '>=', $now->copy()->subDays(30));
                        break;
                    case '90d':
                        $commissions->where('created_at', '>=', $now->copy()->subDays(90));
                        break;
                    case '1y':
                        $commissions->where('created_at', '>=', $now->copy()->subYear());
                        break;
                }
            }

            $data = $commissions->get()->map(function ($commission) {
                return [
                    'ID' => $commission->id,
                    'Business Name' => $commission->business->name ?? 'N/A',
                    'Business Type' => $commission->business->business_type ?? 'N/A',
                    'Affiliate Name' => $commission->affiliate->name ?? 'N/A',
                    'Affiliate Email' => $commission->affiliate->email ?? 'N/A',
                    'QR Code ID' => $commission->qr_code_id,
                    'Amount' => $commission->commission_amount,
                    'Currency' => $commission->currency ?? 'EUR',
                    'Status' => $commission->status,
                    'Created Date' => $commission->created_at->format('Y-m-d H:i:s'),
                    'Paid Date' => $commission->paid_at?->format('Y-m-d H:i:s') ?? 'N/A',
                ];
            });

            $filename = 'commissions-export-' . date('Y-m-d') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ];

            $callback = function () use ($data) {
                $file = fopen('php://output', 'w');

                // Header row
                if ($data->isNotEmpty()) {
                    fputcsv($file, array_keys($data->first()));
                }

                // Data rows
                foreach ($data as $row) {
                    fputcsv($file, $row);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting commissions: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get QR analytics data
     */
    public function getQrAnalyticsData(Request $request): JsonResponse
    {
        try {
            $dateRange = $request->get('date_range', '30d');
            $now = now();
            $startDate = match ($dateRange) {
                '7d' => $now->copy()->subDays(7),
                '30d' => $now->copy()->subDays(30),
                '90d' => $now->copy()->subDays(90),
                '1y' => $now->copy()->subYear(),
                default => $now->copy()->subDays(30),
            };

            $qrCodes = \App\Models\Affiliate\AffiliateQrCode::with(['business'])
                ->where('created_at', '>=', $startDate);

            if ($request->filled('business_id') && $request->business_id !== 'all') {
                $qrCodes->where('business_id', $request->business_id);
            }

            $qrCodes = $qrCodes->get();

            $analytics = [
                'overview' => [
                    'total_qr_codes' => $qrCodes->count(),
                    'total_scans' => $qrCodes->sum('total_scans'),
                    'unique_scans' => $qrCodes->sum('unique_scans'),
                    'conversion_rate' => $qrCodes->avg('conversion_rate') ?? 0,
                    'active_qr_codes' => $qrCodes->where('status', 'active')->count(),
                    'avg_scans_per_qr' => $qrCodes->count() > 0 ? $qrCodes->sum('total_scans') / $qrCodes->count() : 0,
                    'recent_growth' => $this->calculateQrGrowth($startDate),
                ],
                'top_performers' => $qrCodes->sortByDesc('total_scans')->take(10)->map(function ($qr) {
                    return [
                        'id' => $qr->id,
                        'qr_code_id' => $qr->qr_code_id,
                        'business_name' => $qr->business->name ?? 'N/A',
                        'total_scans' => $qr->total_scans,
                        'unique_scans' => $qr->unique_scans,
                        'conversions' => $qr->conversions,
                        'conversion_rate' => $qr->conversion_rate,
                        'performance' => $this->getPerformanceLevel($qr->conversion_rate),
                    ];
                })->values(),
                'device_stats' => $this->getDeviceStats($startDate),
                'location_stats' => $this->getLocationStats($startDate),
                'qr_codes' => $qrCodes->map(function ($qr) {
                    return [
                        'id' => $qr->id,
                        'qr_code_id' => $qr->qr_code_id,
                        'business_name' => $qr->business->name ?? 'N/A',
                        'business_type' => $qr->business->business_type ?? 'N/A',
                        'total_scans' => $qr->total_scans,
                        'unique_scans' => $qr->unique_scans,
                        'conversions' => $qr->conversions,
                        'conversion_rate' => $qr->conversion_rate,
                        'status' => $qr->status,
                        'performance' => $this->getPerformanceLevel($qr->conversion_rate),
                        'created_at' => $qr->created_at->toISOString(),
                    ];
                }),
            ];

            return response()->json($analytics);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching QR analytics: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export QR analytics data
     */
    public function exportQrAnalytics(Request $request)
    {
        try {
            $qrCodes = \App\Models\Affiliate\AffiliateQrCode::with(['business']);

            if ($request->filled('business_id') && $request->business_id !== 'all') {
                $qrCodes->where('business_id', $request->business_id);
            }

            $data = $qrCodes->get()->map(function ($qr) {
                return [
                    'QR Code ID' => $qr->qr_code_id,
                    'Business Name' => $qr->business->name ?? 'N/A',
                    'Business Type' => $qr->business->business_type ?? 'N/A',
                    'Total Scans' => $qr->total_scans,
                    'Unique Scans' => $qr->unique_scans,
                    'Conversions' => $qr->conversions,
                    'Conversion Rate' => number_format($qr->conversion_rate * 100, 2) . '%',
                    'Status' => $qr->status,
                    'Created Date' => $qr->created_at->format('Y-m-d H:i:s'),
                ];
            });

            $filename = 'qr-analytics-export-' . date('Y-m-d') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ];

            $callback = function () use ($data) {
                $file = fopen('php://output', 'w');

                // Header row
                if ($data->isNotEmpty()) {
                    fputcsv($file, array_keys($data->first()));
                }

                // Data rows
                foreach ($data as $row) {
                    fputcsv($file, $row);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting QR analytics: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Helper methods
    private function getTopAffiliates($startDate)
    {
        return \App\Models\Affiliate\AffiliateCommission::where('created_at', '>=', $startDate)
            ->with(['affiliate'])
            ->selectRaw('affiliate_id, SUM(commission_amount) as total_commissions, COUNT(*) as total_transactions')
            ->groupBy('affiliate_id')
            ->orderBy('total_commissions', 'desc')
            ->take(10)
            ->get()
            ->map(function ($item, $index) {
                $affiliate = $item->affiliate;
                return [
                    'id' => $affiliate->id ?? null,
                    'rank' => $index + 1,
                    'name' => $affiliate->name ?? 'N/A',
                    'email' => $affiliate->email ?? 'N/A',
                    'total_commissions' => $item->total_commissions,
                    'total_transactions' => $item->total_transactions,
                    'conversion_rate' => $item->total_transactions > 0 ? ($item->total_transactions / $item->total_transactions) : 0,
                ];
            })
            ->values();
    }

    private function calculateMonthlyGrowth($startDate)
    {
        $currentPeriod = \App\Models\Affiliate\AffiliateCommission::where('created_at', '>=', $startDate);
        $previousPeriod = \App\Models\Affiliate\AffiliateCommission::where('created_at', '>=', $startDate->copy()->subDays($startDate->diffInDays(now())))
            ->where('created_at', '<', $startDate);

        $currentCount = $currentPeriod->count();
        $previousCount = $previousPeriod->count();

        if ($previousCount === 0) {
            return $currentCount > 0 ? 100 : 0;
        }

        return (($currentCount - $previousCount) / $previousCount) * 100;
    }

  
    private function getDeviceStats($startDate)
    {
        // This would typically come from analytics tracking
        // For now, return mock data
        return [
            'mobile' => 65,
            'desktop' => 25,
            'tablet' => 8,
            'other' => 2,
        ];
    }

    private function getLocationStats($startDate)
    {
        // This would typically come from analytics tracking with real location data
        // For now, return mock data
        return [
            [
                'location' => 'New York',
                'country' => 'United States',
                'scans' => 1250,
                'unique_scans' => 980,
                'conversions' => 145,
            ],
            [
                'location' => 'London',
                'country' => 'United Kingdom',
                'scans' => 980,
                'unique_scans' => 750,
                'conversions' => 112,
            ],
            [
                'location' => 'Paris',
                'country' => 'France',
                'scans' => 750,
                'unique_scans' => 620,
                'conversions' => 89,
            ],
        ];
    }

    private function getPerformanceLevel($conversionRate)
    {
        if ($conversionRate >= 0.15) return 'excellent';
        if ($conversionRate >= 0.10) return 'good';
        if ($conversionRate >= 0.05) return 'average';
        return 'poor';
    }

    /**
     * Verify a business
     */
    public function verifyBusiness($businessId): JsonResponse
    {
        try {
            $business = \App\Models\Affiliate\AffiliateBusiness::findOrFail($businessId);
            $business->update(['verification_status' => 'verified']);

            return response()->json([
                'success' => true,
                'message' => 'Business verified successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error verifying business: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject a business
     */
    public function rejectBusiness($businessId): JsonResponse
    {
        try {
            $business = \App\Models\Affiliate\AffiliateBusiness::findOrFail($businessId);
            $business->update(['verification_status' => 'rejected']);

            return response()->json([
                'success' => true,
                'message' => 'Business rejected successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error rejecting business: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Suspend a business
     */
    public function suspendBusiness($businessId): JsonResponse
    {
        try {
            $business = \App\Models\Affiliate\AffiliateBusiness::findOrFail($businessId);
            $business->update(['status' => 'suspended']);

            return response()->json([
                'success' => true,
                'message' => 'Business suspended successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error suspending business: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activate a business
     */
    public function activateBusiness($businessId): JsonResponse
    {
        try {
            $business = \App\Models\Affiliate\AffiliateBusiness::findOrFail($businessId);
            $business->update(['status' => 'active']);

            return response()->json([
                'success' => true,
                'message' => 'Business activated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error activating business: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk verify businesses
     */
    public function bulkVerifyBusinesses(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'business_ids' => 'required|array',
                'business_ids.*' => 'required|integer'
            ]);

            $updated = \App\Models\Affiliate\AffiliateBusiness::whereIn('id', $data['business_ids'])
                ->update(['verification_status' => 'verified']);

            return response()->json([
                'success' => true,
                'message' => "{$updated} businesses verified successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error bulk verifying businesses: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk reject businesses
     */
    public function bulkRejectBusinesses(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'business_ids' => 'required|array',
                'business_ids.*' => 'required|integer'
            ]);

            $updated = \App\Models\Affiliate\AffiliateBusiness::whereIn('id', $data['business_ids'])
                ->update(['verification_status' => 'rejected']);

            return response()->json([
                'success' => true,
                'message' => "{$updated} businesses rejected successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error bulk rejecting businesses: ' . $e->getMessage(),
            ], 500);
        }
    }
}