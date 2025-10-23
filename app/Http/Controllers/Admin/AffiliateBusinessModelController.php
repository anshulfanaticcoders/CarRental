<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Affiliate\AffiliateBusinessModelService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

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
                'contact_phone' => $business->contact_phone,
                'city' => $business->city,
                'country' => $business->country,
                'status' => $business->status,
                'verification_status' => $business->verification_status,
                'created_at' => $business->created_at,
                'applied_at' => $business->created_at,
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
            $dateRange = $request->get('date_range', '30d');
            $now = now();
            $startDate = match ($dateRange) {
                '7d' => $now->copy()->subDays(7),
                '30d' => $now->copy()->subDays(30),
                '90d' => $now->copy()->subDays(90),
                '1y' => $now->copy()->subYear(),
                default => $now->copy()->subDays(30),
            };

            // Get businesses with their relationships for comprehensive statistics
            $businessesQuery = \App\Models\Affiliate\AffiliateBusiness::with(['qrCodes', 'commissions', 'customerScans']);

            // Apply date filtering for the relevant data
            $businesses = $businessesQuery->get();

            // Calculate overview statistics
            $totalBusinesses = $businesses->count();
            $activeBusinesses = $businesses->where('status', 'active')->count();
            $pendingVerification = $businesses->where('verification_status', 'pending')->count();

            // Aggregate data across all businesses
            $totalQrCodes = $businesses->sum(function ($business) use ($startDate) {
                return $business->qrCodes()->where('created_at', '>=', $startDate)->count();
            });

            $totalScans = $businesses->sum(function ($business) use ($startDate) {
                return $business->qrCodes()->where('created_at', '>=', $startDate)->sum('total_scans');
            });

            $totalRevenue = $businesses->sum(function ($business) use ($startDate) {
                return $business->qrCodes()->where('created_at', '>=', $startDate)->sum('total_revenue_generated');
            });

            $totalCommissions = $businesses->sum(function ($business) use ($startDate) {
                return $business->commissions()->where('created_at', '>=', $startDate)->sum('commission_amount');
            });

            $pendingPayouts = $businesses->sum(function ($business) {
                return $business->commissions()->where('status', 'approved')->sum('commission_amount');
            });

            // Calculate conversion rate safely
            $avgConversionRate = 0;
            if ($totalScans > 0) {
                $totalConversions = $businesses->sum(function ($business) use ($startDate) {
                    return $business->qrCodes()->where('created_at', '>=', $startDate)->sum('conversion_count');
                });
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
                'avg_conversion_rate' => round($avgConversionRate, 2),
            ];

            // Generate time-series data for charts
            $chartLabels = $this->generateChartLabels($startDate, $now);
            $revenueData = $this->getRevenueTimeSeries($startDate, $now, $businesses);
            $commissionData = $this->getCommissionTimeSeries($startDate, $now, $businesses);
            $qrCodeData = $this->getQrCodeTimeSeries($startDate, $now, $businesses);
            $conversionData = $this->getConversionTimeSeries($startDate, $now, $businesses);

            // Calculate growth comparisons
            $previousStartDate = $startDate->copy()->subDays($startDate->diffInDays($now));
            $recentGrowth = [
                'businesses' => $this->calculateGrowthRate(
                    $this->getBusinessCountInPeriod($previousStartDate, $startDate),
                    $this->getBusinessCountInPeriod($startDate, $now)
                ),
                'active' => $this->calculateGrowthRate(
                    $businesses->where('status', 'active')->where('created_at', '<', $startDate)->count(),
                    $businesses->where('status', 'active')->where('created_at', '>=', $startDate)->count()
                ),
                'qr_codes' => $this->calculateGrowthRate(
                    $businesses->sum(function ($business) use ($previousStartDate, $startDate) {
                        return $business->qrCodes()->where('created_at', '>=', $previousStartDate)
                            ->where('created_at', '<', $startDate)->count();
                    }),
                    $totalQrCodes
                ),
                'scans' => $this->calculateGrowthRate(
                    $businesses->sum(function ($business) use ($previousStartDate, $startDate) {
                        return $business->qrCodes()->where('created_at', '>=', $previousStartDate)
                            ->where('created_at', '<', $startDate)->sum('total_scans');
                    }),
                    $totalScans
                ),
                'revenue' => $this->calculateGrowthRate(
                    $businesses->sum(function ($business) use ($previousStartDate, $startDate) {
                        return $business->qrCodes()->where('created_at', '>=', $previousStartDate)
                            ->where('created_at', '<', $startDate)->sum('total_revenue_generated');
                    }),
                    $totalRevenue
                ),
                'commissions' => $this->calculateGrowthRate(
                    $businesses->sum(function ($business) use ($previousStartDate, $startDate) {
                        return $business->commissions()->where('created_at', '>=', $previousStartDate)
                            ->where('created_at', '<', $startDate)->sum('commission_amount');
                    }),
                    $totalCommissions
                ),
            ];

            // Get top performing businesses
            $topPerformers = $businesses->sortByDesc(function ($business) use ($startDate) {
                return $business->qrCodes()->where('created_at', '>=', $startDate)->sum('total_scans');
            })->take(5)->map(function ($business) use ($startDate) {
                $qrCodes = $business->qrCodes()->where('created_at', '>=', $startDate)->get();
                $totalScans = $qrCodes->sum('total_scans');
                $totalRevenue = $qrCodes->sum('total_revenue_generated');
                $conversionRate = 0;

                if ($totalScans > 0) {
                    $totalConversions = $qrCodes->sum('conversion_count');
                    $conversionRate = ($totalConversions / $totalScans) * 100;
                }

                return [
                    'id' => $business->id,
                    'name' => $business->name,
                    'business_type' => $business->business_type,
                    'total_scans' => $totalScans,
                    'total_revenue' => $totalRevenue,
                    'conversion_rate' => round($conversionRate, 2),
                ];
            })->values();

            // Generate recent activity feed
            $recentActivity = [
                [
                    'id' => 1,
                    'type' => 'success',
                    'description' => $totalQrCodes . ' QR codes generated in selected period',
                    'created_at' => now()->toISOString(),
                ],
                [
                    'id' => 2,
                    'type' => 'info',
                    'description' => '€' . number_format($totalRevenue, 2) . ' revenue generated',
                    'created_at' => now()->subMinutes(30)->toISOString(),
                ],
                [
                    'id' => 3,
                    'type' => 'warning',
                    'description' => $pendingVerification . ' businesses pending verification',
                    'created_at' => now()->subHours(2)->toISOString(),
                ],
                [
                    'id' => 4,
                    'type' => 'success',
                    'description' => round($avgConversionRate, 1) . '% average conversion rate',
                    'created_at' => now()->subHours(4)->toISOString(),
                ],
                [
                    'id' => 5,
                    'type' => 'info',
                    'description' => $activeBusinesses . ' active businesses in platform',
                    'created_at' => now()->subHours(6)->toISOString(),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'overview' => $overview,
                    'chart_labels' => $chartLabels,
                    'revenue_data' => $revenueData,
                    'commission_data' => $commissionData,
                    'qr_code_data' => $qrCodeData,
                    'conversion_data' => $conversionData,
                    'recent_growth' => $recentGrowth,
                    'top_performers' => $topPerformers,
                    'recent' => $recentActivity,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching business statistics: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Helper methods for statistics and chart data generation
     */
    private function calculateGrowthRate($previous, $current): float
    {
        if ($previous == 0) return $current > 0 ? 100 : 0;
        return (($current - $previous) / $previous) * 100;
    }

    private function getBusinessCountInPeriod($startDate, $endDate): int
    {
        return \App\Models\Affiliate\AffiliateBusiness::where('created_at', '>=', $startDate)
            ->where('created_at', '<', $endDate)
            ->count();
    }

    /**
     * Generate chart labels based on date range
     */
    private function generateChartLabels($startDate, $endDate): array
    {
        $labels = [];
        $current = $startDate->copy();
        $totalDays = $startDate->diffInDays($endDate);

        if ($totalDays <= 31) {
            // Daily labels for periods up to a month
            while ($current <= $endDate) {
                $labels[] = $current->format('M j');
                $current->addDay();
            }
        } elseif ($totalDays <= 90) {
            // Weekly labels for up to 3 months
            while ($current <= $endDate) {
                $labels[] = $current->format('M j');
                $current->addWeek();
            }
        } else {
            // Monthly labels for longer periods
            while ($current <= $endDate) {
                $labels[] = $current->format('M Y');
                $current->addMonth();
            }
        }

        return $labels;
    }

    /**
     * Generate time-series data for revenue
     */
    private function getRevenueTimeSeries($startDate, $endDate, $businesses): array
    {
        $data = [];
        $current = $startDate->copy();
        $totalDays = $startDate->diffInDays($endDate);

        if ($totalDays <= 31) {
            // Daily data
            while ($current <= $endDate) {
                $dayRevenue = $businesses->sum(function ($business) use ($current) {
                    return $business->qrCodes()
                        ->whereDate('created_at', $current->toDateString())
                        ->sum('total_revenue_generated');
                });
                $data[] = $dayRevenue;
                $current->addDay();
            }
        } elseif ($totalDays <= 90) {
            // Weekly data
            while ($current <= $endDate) {
                $weekEnd = $current->copy()->addDays(6)->min($endDate);
                $weekRevenue = $businesses->sum(function ($business) use ($current, $weekEnd) {
                    return $business->qrCodes()
                        ->whereBetween('created_at', [$current, $weekEnd])
                        ->sum('total_revenue_generated');
                });
                $data[] = $weekRevenue;
                $current->addWeek();
            }
        } else {
            // Monthly data
            while ($current <= $endDate) {
                $monthEnd = $current->copy()->addMonth()->subDay()->min($endDate);
                $monthRevenue = $businesses->sum(function ($business) use ($current, $monthEnd) {
                    return $business->qrCodes()
                        ->whereBetween('created_at', [$current, $monthEnd])
                        ->sum('total_revenue_generated');
                });
                $data[] = $monthRevenue;
                $current->addMonth();
            }
        }

        return $data;
    }

    /**
     * Generate time-series data for commissions
     */
    private function getCommissionTimeSeries($startDate, $endDate, $businesses): array
    {
        $data = [];
        $current = $startDate->copy();
        $totalDays = $startDate->diffInDays($endDate);

        if ($totalDays <= 31) {
            // Daily data
            while ($current <= $endDate) {
                $dayCommissions = $businesses->sum(function ($business) use ($current) {
                    return $business->commissions()
                        ->whereDate('created_at', $current->toDateString())
                        ->sum('commission_amount');
                });
                $data[] = $dayCommissions;
                $current->addDay();
            }
        } elseif ($totalDays <= 90) {
            // Weekly data
            while ($current <= $endDate) {
                $weekEnd = $current->copy()->addDays(6)->min($endDate);
                $weekCommissions = $businesses->sum(function ($business) use ($current, $weekEnd) {
                    return $business->commissions()
                        ->whereBetween('created_at', [$current, $weekEnd])
                        ->sum('commission_amount');
                });
                $data[] = $weekCommissions;
                $current->addWeek();
            }
        } else {
            // Monthly data
            while ($current <= $endDate) {
                $monthEnd = $current->copy()->addMonth()->subDay()->min($endDate);
                $monthCommissions = $businesses->sum(function ($business) use ($current, $monthEnd) {
                    return $business->commissions()
                        ->whereBetween('created_at', [$current, $monthEnd])
                        ->sum('commission_amount');
                });
                $data[] = $monthCommissions;
                $current->addMonth();
            }
        }

        return $data;
    }

    /**
     * Generate time-series data for QR codes
     */
    private function getQrCodeTimeSeries($startDate, $endDate, $businesses): array
    {
        $data = [];
        $current = $startDate->copy();
        $totalDays = $startDate->diffInDays($endDate);

        if ($totalDays <= 31) {
            // Daily data
            while ($current <= $endDate) {
                $dayQrCodes = $businesses->sum(function ($business) use ($current) {
                    return $business->qrCodes()
                        ->whereDate('created_at', $current->toDateString())
                        ->count();
                });
                $data[] = $dayQrCodes;
                $current->addDay();
            }
        } elseif ($totalDays <= 90) {
            // Weekly data
            while ($current <= $endDate) {
                $weekEnd = $current->copy()->addDays(6)->min($endDate);
                $weekQrCodes = $businesses->sum(function ($business) use ($current, $weekEnd) {
                    return $business->qrCodes()
                        ->whereBetween('created_at', [$current, $weekEnd])
                        ->count();
                });
                $data[] = $weekQrCodes;
                $current->addWeek();
            }
        } else {
            // Monthly data
            while ($current <= $endDate) {
                $monthEnd = $current->copy()->addMonth()->subDay()->min($endDate);
                $monthQrCodes = $businesses->sum(function ($business) use ($current, $monthEnd) {
                    return $business->qrCodes()
                        ->whereBetween('created_at', [$current, $monthEnd])
                        ->count();
                });
                $data[] = $monthQrCodes;
                $current->addMonth();
            }
        }

        return $data;
    }

    /**
     * Generate time-series data for conversion rates
     */
    private function getConversionTimeSeries($startDate, $endDate, $businesses): array
    {
        $data = [];
        $current = $startDate->copy();
        $totalDays = $startDate->diffInDays($endDate);

        if ($totalDays <= 31) {
            // Daily data
            while ($current <= $endDate) {
                $dayScans = $businesses->sum(function ($business) use ($current) {
                    return $business->qrCodes()
                        ->whereDate('created_at', $current->toDateString())
                        ->sum('total_scans');
                });

                $dayConversions = $businesses->sum(function ($business) use ($current) {
                    return $business->qrCodes()
                        ->whereDate('created_at', $current->toDateString())
                        ->sum('conversion_count');
                });

                $conversionRate = $dayScans > 0 ? ($dayConversions / $dayScans) * 100 : 0;
                $data[] = round($conversionRate, 2);
                $current->addDay();
            }
        } elseif ($totalDays <= 90) {
            // Weekly data
            while ($current <= $endDate) {
                $weekEnd = $current->copy()->addDays(6)->min($endDate);

                $weekScans = $businesses->sum(function ($business) use ($current, $weekEnd) {
                    return $business->qrCodes()
                        ->whereBetween('created_at', [$current, $weekEnd])
                        ->sum('total_scans');
                });

                $weekConversions = $businesses->sum(function ($business) use ($current, $weekEnd) {
                    return $business->qrCodes()
                        ->whereBetween('created_at', [$current, $weekEnd])
                        ->sum('conversion_count');
                });

                $conversionRate = $weekScans > 0 ? ($weekConversions / $weekScans) * 100 : 0;
                $data[] = round($conversionRate, 2);
                $current->addWeek();
            }
        } else {
            // Monthly data
            while ($current <= $endDate) {
                $monthEnd = $current->copy()->addMonth()->subDay()->min($endDate);

                $monthScans = $businesses->sum(function ($business) use ($current, $monthEnd) {
                    return $business->qrCodes()
                        ->whereBetween('created_at', [$current, $monthEnd])
                        ->sum('total_scans');
                });

                $monthConversions = $businesses->sum(function ($business) use ($current, $monthEnd) {
                    return $business->qrCodes()
                        ->whereBetween('created_at', [$current, $monthEnd])
                        ->sum('conversion_count');
                });

                $conversionRate = $monthScans > 0 ? ($monthConversions / $monthScans) * 100 : 0;
                $data[] = round($conversionRate, 2);
                $current->addMonth();
            }
        }

        return $data;
    }

    /**
     * Generate time-series data for commission revenue from QR codes
     */
    private function getCommissionRevenueTimeSeries($startDate, $endDate, $businesses): array
    {
        $data = [];
        $current = $startDate->copy();
        $totalDays = $startDate->diffInDays($endDate);

        if ($totalDays <= 31) {
            // Daily data
            while ($current <= $endDate) {
                $dayRevenue = $businesses->sum(function ($business) use ($current) {
                    return $business->qrCodes()
                        ->whereDate('created_at', $current->toDateString())
                        ->sum('total_revenue_generated');
                });
                $data[] = $dayRevenue;
                $current->addDay();
            }
        } elseif ($totalDays <= 90) {
            // Weekly data
            while ($current <= $endDate) {
                $weekEnd = $current->copy()->addDays(6)->min($endDate);
                $weekRevenue = $businesses->sum(function ($business) use ($current, $weekEnd) {
                    return $business->qrCodes()
                        ->whereBetween('created_at', [$current, $weekEnd])
                        ->sum('total_revenue_generated');
                });
                $data[] = $weekRevenue;
                $current->addWeek();
            }
        } else {
            // Monthly data
            while ($current <= $endDate) {
                $monthEnd = $current->copy()->addMonth()->subDay()->min($endDate);
                $monthRevenue = $businesses->sum(function ($business) use ($current, $monthEnd) {
                    return $business->qrCodes()
                        ->whereBetween('created_at', [$current, $monthEnd])
                        ->sum('total_revenue_generated');
                });
                $data[] = $monthRevenue;
                $current->addMonth();
            }
        }

        return $data;
    }

    /**
     * Generate time-series data for commission amounts
     */
    private function getCommissionAmountTimeSeries($startDate, $endDate, $businesses): array
    {
        return $this->getCommissionTimeSeries($startDate, $endDate, $businesses);
    }

    /**
     * Generate time-series data for commission counts
     */
    private function getCommissionCountTimeSeries($startDate, $endDate, $businesses): array
    {
        $data = [];
        $current = $startDate->copy();
        $totalDays = $startDate->diffInDays($endDate);

        if ($totalDays <= 31) {
            // Daily data
            while ($current <= $endDate) {
                $dayCount = $businesses->sum(function ($business) use ($current) {
                    return $business->commissions()
                        ->whereDate('created_at', $current->toDateString())
                        ->count();
                });
                $data[] = $dayCount;
                $current->addDay();
            }
        } elseif ($totalDays <= 90) {
            // Weekly data
            while ($current <= $endDate) {
                $weekEnd = $current->copy()->addDays(6)->min($endDate);
                $weekCount = $businesses->sum(function ($business) use ($current, $weekEnd) {
                    return $business->commissions()
                        ->whereBetween('created_at', [$current, $weekEnd])
                        ->count();
                });
                $data[] = $weekCount;
                $current->addWeek();
            }
        } else {
            // Monthly data
            while ($current <= $endDate) {
                $monthEnd = $current->copy()->addMonth()->subDay()->min($endDate);
                $monthCount = $businesses->sum(function ($business) use ($current, $monthEnd) {
                    return $business->commissions()
                        ->whereBetween('created_at', [$current, $monthEnd])
                        ->count();
                });
                $data[] = $monthCount;
                $current->addMonth();
            }
        }

        return $data;
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
     * Get business details for API
     */
    public function getBusinessDetails($businessId): JsonResponse
    {
        try {
            // Load business without problematic relationships first
            $business = \App\Models\Affiliate\AffiliateBusiness::findOrFail($businessId);

            // Load relationships separately to isolate any issues
            try {
                $qrCodes = $business->qrCodes()->get();
            } catch (\Exception $e) {
                $qrCodes = collect([]);
            }

            try {
                $commissions = $business->commissions()->get();
            } catch (\Exception $e) {
                $commissions = collect([]);
            }

            try {
                $locations = $business->locations()->get();
            } catch (\Exception $e) {
                $locations = collect([]);
            }

            // Calculate statistics safely
            $totalScans = $qrCodes->sum('total_scans');
            $totalConversions = $qrCodes->sum('conversion_count');
            $conversionRate = $totalScans > 0 ? ($totalConversions / $totalScans) * 100 : 0;

            $statistics = [
                'total_scans' => $totalScans,
                'unique_scans' => $qrCodes->sum('unique_scans'),
                'total_revenue' => $qrCodes->sum('total_revenue_generated'),
                'conversion_rate' => round($conversionRate, 2),
            ];

            // Get recent activity
            $recentActivity = [
                [
                    'id' => 1,
                    'type' => 'success',
                    'description' => $qrCodes->count() . ' QR codes generated',
                    'created_at' => $business->created_at->toISOString(),
                ],
                [
                    'id' => 2,
                    'type' => 'info',
                    'description' => '€' . number_format($statistics['total_revenue'], 2) . ' total revenue generated',
                    'created_at' => $business->updated_at->toISOString(),
                ],
            ];

            return response()->json([
                'success' => true,
                'business' => $business,
                'locations' => $locations,
                'qr_codes' => $qrCodes,
                'commissions' => $commissions,
                'statistics' => $statistics,
                'recent_activity' => $recentActivity,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching business details: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Update business details
     */
    public function updateBusinessDetails(Request $request, $businessId): JsonResponse
    {
        try {
            $business = \App\Models\Affiliate\AffiliateBusiness::findOrFail($businessId);

            $data = $request->validate([
                'name' => 'nullable|string|max:255',
                'contact_email' => 'nullable|email|max:255',
                'contact_phone' => 'nullable|string|max:20',
                'website' => 'nullable|url|max:255',
                'legal_address' => 'nullable|string',
                'billing_address' => 'nullable|string',
            ]);

            $business->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Business updated successfully',
                'business' => $business->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating business: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export business data
     */
    public function exportBusinessData($businessId)
    {
        try {
            $business = \App\Models\Affiliate\AffiliateBusiness::with([
                'qrCodes',
                'commissions'
            ])->findOrFail($businessId);

            $data = [
                [
                    'Business Name',
                    'Business Type',
                    'Contact Email',
                    'Contact Phone',
                    'Status',
                    'Verification Status',
                    'Created Date',
                    'Total QR Codes',
                    'Total Scans',
                    'Total Revenue',
                    'Total Commissions'
                ],
                [
                    $business->name,
                    $business->business_type,
                    $business->contact_email,
                    $business->contact_phone,
                    $business->status,
                    $business->verification_status,
                    $business->created_at->format('Y-m-d H:i:s'),
                    $business->qrCodes->count(),
                    $business->qrCodes->sum('total_scans'),
                    $business->qrCodes->sum('total_revenue_generated'),
                    $business->commissions->sum('commission_amount'),
                ]
            ];

            $filename = 'business-' . $business->name . '-' . date('Y-m-d') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ];

            $callback = function () use ($data) {
                $file = fopen('php://output', 'w');

                foreach ($data as $row) {
                    fputcsv($file, $row);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting business data: ' . $e->getMessage(),
            ], 500);
        }
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
            // Start with basic query
            $query = \App\Models\Affiliate\AffiliateCommission::with(['business', 'customer', 'booking.customer'])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
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

            // Apply search filter if present
            if ($request->filled('search') && strlen(trim($request->search)) > 0) {
                $search = trim($request->search);

                // Optimized search using direct field matches first (faster than subqueries)
                $query->where(function ($q) use ($search) {
                    // Direct commission table searches (fastest)
                    $q->where('affiliate_commissions.uuid', 'like', "%{$search}%")
                      ->orWhere('affiliate_commissions.qr_scan_id', 'like', "%{$search}%")
                      ->orWhere('affiliate_commissions.booking_id', 'like', "%{$search}%");
                })
                // Only use subquery for business name if needed
                ->orWhereHas('business', function ($businessQuery) use ($search) {
                    $businessQuery->where('name', 'like', "%{$search}%");
                });
            }

            $perPage = $request->get('per_page', 5);
            $commissions = $query->paginate($perPage);

            $formattedCommissions = $commissions->getCollection()->map(function ($commission) {
                // Get business currency or default to EUR
                $currency = $commission->business ? $commission->business->currency : 'EUR';

                // Handle customer data - try to get from customer relationship, then from booking, then fallback
                $customerName = 'N/A';
                $customerEmail = 'N/A';

                if ($commission->customer) {
                    // User model has first_name and last_name fields, not name
                    $firstName = $commission->customer->first_name;
                    $lastName = $commission->customer->last_name;
                    $customerName = trim(($firstName ?? '') . ' ' . ($lastName ?? '')) ?: 'N/A';
                    $customerEmail = $commission->customer->email;
                } elseif ($commission->booking && $commission->booking->customer) {
                    // Try to get name from booking's customer relationship
                    $firstName = $commission->booking->customer->first_name ?? null;
                    $lastName = $commission->booking->customer->last_name ?? null;
                    $customerName = trim(($firstName ?? '') . ' ' . ($lastName ?? '')) ?: 'N/A';
                    $customerEmail = $commission->booking->customer->email ?? 'N/A';
                }

                return [
                    'id' => $commission->id,
                    'business_name' => $commission->business ? $commission->business->name : 'N/A',
                    'business_type' => $commission->business ? $commission->business->business_type : 'N/A',
                    'affiliate_name' => $customerName,
                    'affiliate_email' => $customerEmail,
                    'qr_code_id' => $commission->qr_scan_id,
                    'booking_reference' => $commission->booking && $commission->booking->booking_reference
                        ? $commission->booking->booking_reference
                        : 'QR-' . $commission->qr_scan_id,
                    'booking_amount' => $commission->booking_amount ?: 0,
                    'amount' => $commission->commission_amount,
                    'currency' => $currency,
                    'status' => $commission->status,
                    'created_at' => $commission->created_at->toISOString(),
                    'paid_at' => $commission->paid_at ? $commission->paid_at->toISOString() : null,
                    'scheduled_payout_date' => $commission->scheduled_payout_date ?: null,
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

            $commissions = \App\Models\Affiliate\AffiliateCommission::with(['business', 'customer'])
                ->where('created_at', '>=', $startDate)
                ->get(); // FIX: Add get() to execute the query

            $totalCommissions = $commissions->sum('commission_amount');
            $pendingCommissions = $commissions->where('status', 'pending')->sum('commission_amount');
            $approvedCommissions = $commissions->where('status', 'approved')->sum('commission_amount');
            $paidCommissions = $commissions->where('status', 'paid')->sum('commission_amount');
            $disputedCommissions = $commissions->where('status', 'disputed')->sum('commission_amount');
            $rejectedCommissions = $commissions->where('status', 'rejected')->sum('commission_amount');

            // Get businesses for time-series data generation
            $businesses = \App\Models\Affiliate\AffiliateBusiness::with(['commissions', 'qrCodes'])->get();

            // Generate time-series data for charts
            $chartLabels = $this->generateChartLabels($startDate, $now);
            $revenueData = $this->getCommissionRevenueTimeSeries($startDate, $now, $businesses);
            $commissionData = $this->getCommissionAmountTimeSeries($startDate, $now, $businesses);
            $commissionCountData = $this->getCommissionCountTimeSeries($startDate, $now, $businesses);

            $stats = [
                'overview' => [
                    'total_commissions' => $commissions->count(),
                    'pending_commissions' => $commissions->where('status', 'pending')->count(),
                    'approved_commissions' => $commissions->where('status', 'approved')->count(),
                    'paid_commissions' => $commissions->where('status', 'paid')->count(),
                    'disputed_commissions' => $commissions->where('status', 'disputed')->count(),
                    'rejected_commissions' => $commissions->where('status', 'rejected')->count(),
                    'total_amount' => $totalCommissions,
                    'pending_amount' => $pendingCommissions,
                    'approved_amount' => $approvedCommissions,
                    'paid_amount' => $paidCommissions,
                    'disputed_amount' => $disputedCommissions,
                    'rejected_amount' => $rejectedCommissions,
                    'average_commission' => $commissions->avg('commission_amount') ?? 0,
                    'conversion_rate' => $commissions->count() > 0 ? round(($commissions->where('status', 'paid')->count() / $commissions->count()) * 100, 2) : 0,
                ],
                'top_affiliates' => $this->getTopAffiliates($startDate),
                'top_businesses' => $this->getTopBusinesses($startDate),
                'monthly_growth' => $this->calculateMonthlyGrowth($startDate),
                'status_distribution' => [
                    'pending' => $commissions->where('status', 'pending')->count(),
                    'approved' => $commissions->where('status', 'approved')->count(),
                    'paid' => $commissions->where('status', 'paid')->count(),
                    'disputed' => $commissions->where('status', 'disputed')->count(),
                    'rejected' => $commissions->where('status', 'rejected')->count(),
                    'cancelled' => $commissions->where('status', 'cancelled')->count(),
                ],
                'currency_breakdown' => $this->getCurrencyBreakdown($startDate),
                // Add time-series data for charts
                'chart_labels' => $chartLabels,
                'revenue_data' => $revenueData,
                'commission_data' => $commissionData,
                'commission_count_data' => $commissionCountData,
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
            $commissions = \App\Models\Affiliate\AffiliateCommission::with(['business', 'customer'])
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
                    'Affiliate Name' => trim(($commission->customer->first_name ?? '') . ' ' . ($commission->customer->last_name ?? '')) ?: 'N/A',
                    'Affiliate Email' => $commission->customer->email ?? 'N/A',
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
                    'conversion_rate' => $qrCodes->sum('total_scans') > 0 ? round(($qrCodes->sum('conversion_count') / $qrCodes->sum('total_scans')) * 100, 2) : 0,
                    'active_qr_codes' => $qrCodes->where('status', 'active')->count(),
                    'avg_scans_per_qr' => $qrCodes->count() > 0 ? $qrCodes->sum('total_scans') / $qrCodes->count() : 0,
                    'recent_growth' => $this->calculateQrGrowth($startDate),
                ],
                'top_performers' => $qrCodes->sortByDesc('total_scans')->take(10)->map(function ($qr) {
                    $conversionRate = $qr->total_scans > 0 ? ($qr->conversion_count / $qr->total_scans) * 100 : 0;
                    return [
                        'id' => $qr->id,
                        'qr_code_id' => $qr->qr_code_id,
                        'business_name' => $qr->business->name ?? 'N/A',
                        'total_scans' => $qr->total_scans,
                        'unique_scans' => $qr->unique_scans,
                        'conversions' => $qr->conversion_count,
                        'conversion_rate' => round($conversionRate, 2),
                        'performance' => $this->getPerformanceLevel($conversionRate / 100),
                    ];
                })->values(),
                'device_stats' => $this->getDeviceStats($startDate),
                'location_stats' => $this->getLocationStats($startDate),
                'qr_codes' => $qrCodes->map(function ($qr) {
                    $conversionRate = $qr->total_scans > 0 ? ($qr->conversion_count / $qr->total_scans) * 100 : 0;
                    return [
                        'id' => $qr->id,
                        'qr_code_id' => $qr->qr_code_id,
                        'business_name' => $qr->business->name ?? 'N/A',
                        'business_type' => $qr->business->business_type ?? 'N/A',
                        'total_scans' => $qr->total_scans,
                        'unique_scans' => $qr->unique_scans,
                        'conversions' => $qr->conversion_count,
                        'conversion_rate' => round($conversionRate, 2),
                        'status' => $qr->status,
                        'performance' => $this->getPerformanceLevel($conversionRate / 100),
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
                $conversionRate = $qr->total_scans > 0 ? ($qr->conversion_count / $qr->total_scans) * 100 : 0;
                return [
                    'QR Code ID' => $qr->qr_code_id,
                    'Business Name' => $qr->business->name ?? 'N/A',
                    'Business Type' => $qr->business->business_type ?? 'N/A',
                    'Total Scans' => $qr->total_scans,
                    'Unique Scans' => $qr->unique_scans,
                    'Conversions' => $qr->conversion_count,
                    'Conversion Rate' => number_format($conversionRate, 2) . '%',
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
            ->with(['customer'])
            ->selectRaw('customer_id, SUM(commission_amount) as total_commissions, COUNT(*) as total_transactions')
            ->groupBy('customer_id')
            ->orderBy('total_commissions', 'desc')
            ->take(10)
            ->get()
            ->map(function ($item, $index) {
                $customer = $item->customer;
                return [
                    'id' => $customer->id ?? null,
                    'rank' => $index + 1,
                    'name' => trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: 'N/A',
                    'email' => $customer->email ?? 'N/A',
                    'total_commissions' => $item->total_commissions,
                    'total_transactions' => $item->total_transactions,
                    'conversion_rate' => $item->total_transactions > 0 ? ($item->total_transactions / $item->total_transactions) : 0,
                ];
            })
            ->values();
    }

    private function getTopBusinesses($startDate)
    {
        return \App\Models\Affiliate\AffiliateCommission::where('created_at', '>=', $startDate)
            ->with(['business'])
            ->selectRaw('business_id, SUM(commission_amount) as total_commissions, COUNT(*) as total_transactions')
            ->groupBy('business_id')
            ->orderBy('total_commissions', 'desc')
            ->take(10)
            ->get()
            ->map(function ($item, $index) {
                $business = $item->business;
                return [
                    'id' => $business->id ?? null,
                    'rank' => $index + 1,
                    'name' => $business->name ?? 'N/A',
                    'business_type' => $business->business_type ?? 'N/A',
                    'total_commissions' => $item->total_commissions,
                    'total_transactions' => $item->total_transactions,
                    'average_commission' => $item->total_transactions > 0 ? $item->total_commissions / $item->total_transactions : 0,
                ];
            })
            ->values();
    }

    private function getCurrencyBreakdown($startDate)
    {
        try {
            $commissions = \App\Models\Affiliate\AffiliateCommission::with(['business'])
                ->where('created_at', '>=', $startDate)
                ->get();

            $currencyBreakdown = [];
            foreach ($commissions as $commission) {
                $currency = $commission->business->currency ?? 'EUR';
                if (!isset($currencyBreakdown[$currency])) {
                    $currencyBreakdown[$currency] = [
                        'total_amount' => 0,
                        'count' => 0
                    ];
                }
                $currencyBreakdown[$currency]['total_amount'] += $commission->commission_amount;
                $currencyBreakdown[$currency]['count']++;
            }

            // Convert to array and sort by total amount
            $sortedBreakdown = collect($currencyBreakdown)
                ->map(function ($amount, $currency) {
                    return [
                        'currency' => $currency,
                        'total_amount' => $amount,
                        'count' => $currencyBreakdown[$currency]['count'],
                        'percentage' => 0, // Will be calculated in frontend
                    ];
                })
                ->sortByDesc('total_amount')
                ->values()
                ->toArray();

            return $sortedBreakdown;
        } catch (\Exception $e) {
            // Return empty array if there's an error
            return [
                [
                    'currency' => 'EUR',
                    'total_amount' => 0,
                    'count' => 0,
                    'percentage' => 0,
                ]
            ];
        }
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