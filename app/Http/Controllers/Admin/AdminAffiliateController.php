<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate\AffiliateBusiness;
use App\Models\Affiliate\AffiliateCommission;
use App\Models\Affiliate\AffiliatePayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminAffiliateController extends Controller
{
    public function overview()
    {
        $totalPartners = AffiliateBusiness::count();
        $activePartners = AffiliateBusiness::where('status', 'active')->count();
        $pendingVerification = AffiliateBusiness::where('verification_status', 'pending')->count();
        $totalRevenue = AffiliateCommission::sum('commission_amount');
        $pendingPayouts = AffiliatePayout::where('status', 'pending')->sum('total_amount');

        // Revenue trend: last 30 days grouped by date
        $revenueTrend = AffiliateCommission::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(commission_amount) as revenue')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        // Top 5 affiliates by total commission
        $topAffiliates = AffiliateBusiness::withSum('commissions', 'commission_amount')
            ->orderByDesc('commissions_sum_commission_amount')
            ->limit(5)
            ->get()
            ->map(fn ($b) => [
                'name' => $b->name,
                'revenue' => round((float) $b->commissions_sum_commission_amount, 2),
            ]);

        // Recent 5 commissions
        $recentCommissions = AffiliateCommission::with('business:id,name')
            ->latest()
            ->limit(5)
            ->get();

        return Inertia::render('AdminDashboardPages/Affiliate/Overview', [
            'stats' => [
                'totalPartners' => $totalPartners,
                'activePartners' => $activePartners,
                'pendingVerification' => $pendingVerification,
                'totalRevenue' => round((float) $totalRevenue, 2),
                'pendingPayouts' => round((float) $pendingPayouts, 2),
            ],
            'revenueTrend' => $revenueTrend,
            'topAffiliates' => $topAffiliates,
            'recentCommissions' => $recentCommissions,
        ]);
    }

    public function partners(Request $request)
    {
        $query = AffiliateBusiness::query()
            ->withCount('qrCodes')
            ->withSum('commissions', 'commission_amount');

        if ($search = $request->input('search')) {
            $query->search($search);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($verification = $request->input('verification')) {
            $query->where('verification_status', $verification);
        }

        $partners = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('AdminDashboardPages/Affiliate/Partners', [
            'partners' => $partners,
            'filters' => $request->only(['search', 'status', 'verification']),
            'stats' => [
                'total' => AffiliateBusiness::count(),
                'active' => AffiliateBusiness::where('status', 'active')->count(),
                'pending' => AffiliateBusiness::where('status', 'pending')->count(),
                'verified' => AffiliateBusiness::where('verification_status', 'verified')->count(),
            ],
        ]);
    }

    public function partnerDetail($id)
    {
        $partner = AffiliateBusiness::with([
                'user:id,first_name,last_name,email',
                'businessModel',
            ])
            ->withCount('qrCodes')
            ->withSum('commissions', 'commission_amount')
            ->findOrFail($id);

        $commissionStats = [
            'total' => AffiliateCommission::forBusiness($id)->sum('commission_amount'),
            'pending' => AffiliateCommission::forBusiness($id)->pending()->sum('commission_amount'),
            'approved' => AffiliateCommission::forBusiness($id)->approved()->sum('commission_amount'),
            'paid' => AffiliateCommission::forBusiness($id)->paid()->sum('commission_amount'),
        ];

        $commissions = AffiliateCommission::forBusiness($id)
            ->with('booking:id,booking_number,total_amount')
            ->latest()
            ->paginate(15, ['*'], 'commissions_page');

        $qrCodes = $partner->qrCodes()
            ->withCount('customerScans')
            ->latest()
            ->get();

        $totalScans = $qrCodes->sum('customer_scans_count');

        return Inertia::render('AdminDashboardPages/Affiliate/PartnerDetail', [
            'partner' => $partner,
            'commissionStats' => $commissionStats,
            'commissions' => $commissions,
            'qrCodes' => $qrCodes,
            'totalScans' => $totalScans,
        ]);
    }

    public function commissions(Request $request)
    {
        $query = AffiliateCommission::with([
            'business:id,name',
            'booking:id,booking_number,total_amount',
        ]);

        if ($search = $request->input('search')) {
            $query->whereHas('business', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('booking', fn ($q) => $q->where('booking_number', 'like', "%{$search}%"));
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->where('created_at', '<=', $dateTo . ' 23:59:59');
        }

        $commissions = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('AdminDashboardPages/Affiliate/Commissions', [
            'commissions' => $commissions,
            'filters' => $request->only(['search', 'status', 'date_from', 'date_to']),
            'stats' => [
                'total' => AffiliateCommission::count(),
                'totalAmount' => round((float) AffiliateCommission::sum('commission_amount'), 2),
                'pending' => AffiliateCommission::pending()->count(),
                'pendingAmount' => round((float) AffiliateCommission::pending()->sum('commission_amount'), 2),
                'approved' => AffiliateCommission::approved()->count(),
                'approvedAmount' => round((float) AffiliateCommission::approved()->sum('commission_amount'), 2),
                'paid' => AffiliateCommission::paid()->count(),
                'paidAmount' => round((float) AffiliateCommission::paid()->sum('commission_amount'), 2),
            ],
        ]);
    }

    public function updateCommissionStatus(Request $request, $id)
    {
        $commission = AffiliateCommission::findOrFail($id);

        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'reason' => 'required_if:action,reject|nullable|string|max:1000',
        ]);

        if ($validated['action'] === 'approve') {
            $commission->approve(auth()->id());
        } else {
            $commission->reject(auth()->id(), $validated['reason'] ?? null);
        }

        return back()->with('success', 'Commission ' . $validated['action'] . 'd successfully.');
    }
}
