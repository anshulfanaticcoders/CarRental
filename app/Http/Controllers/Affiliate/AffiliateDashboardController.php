<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\Affiliate\AffiliateCommission;
use App\Models\Affiliate\AffiliateGlobalSetting;
use App\Services\Affiliate\AffiliateQrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AffiliateDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $business = $user->affiliateBusiness;

        $totalCommissions = AffiliateCommission::where('business_id', $business->id)->sum('commission_amount');
        $pendingCommissions = AffiliateCommission::where('business_id', $business->id)->pending()->sum('commission_amount');
        $paidCommissions = AffiliateCommission::where('business_id', $business->id)->paid()->sum('commission_amount');
        $totalBookings = AffiliateCommission::where('business_id', $business->id)->count();
        $thisMonthCommissions = AffiliateCommission::where('business_id', $business->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('commission_amount');

        // Total scans across all QR codes
        $totalScans = $business->customerScans()->count();

        // Conversion rate
        $conversionRate = $totalScans > 0 ? round(($totalBookings / $totalScans) * 100, 1) : 0;

        $recentCommissions = AffiliateCommission::where('business_id', $business->id)
            ->with('booking:id,booking_number,total_amount,booking_currency')
            ->latest()
            ->take(10)
            ->get();

        // Active QR codes with scan/booking counts
        $qrCodes = $business->qrCodes()
            ->with('location')
            ->withCount('customerScans')
            ->get()
            ->map(function ($qr) {
                $qr->bookings_count = $qr->customerScans()
                    ->where('booking_completed', true)
                    ->count();
                return $qr;
            });

        return Inertia::render('Affiliate/Dashboard', [
            'business' => $business,
            'stats' => [
                'total_commissions' => round($totalCommissions, 2),
                'pending_commissions' => round($pendingCommissions, 2),
                'paid_commissions' => round($paidCommissions, 2),
                'total_bookings' => $totalBookings,
                'this_month' => round($thisMonthCommissions, 2),
                'total_scans' => $totalScans,
                'conversion_rate' => $conversionRate,
            ],
            'recentCommissions' => $recentCommissions,
            'qrCodes' => $qrCodes,
        ]);
    }

    public function commissions(Request $request)
    {
        $user = $request->user();
        $business = $user->affiliateBusiness;

        $query = AffiliateCommission::where('business_id', $business->id)
            ->with('booking:id,booking_number,total_amount,booking_currency,pickup_date');

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $commissions = $query->latest()->paginate(20)->withQueryString();

        // Summary stats
        $baseQuery = AffiliateCommission::where('business_id', $business->id);
        $summaryStats = [
            'total_earned' => round($baseQuery->clone()->sum('commission_amount'), 2),
            'pending' => round($baseQuery->clone()->pending()->sum('commission_amount'), 2),
            'paid_out' => round($baseQuery->clone()->paid()->sum('commission_amount'), 2),
            'this_month' => round($baseQuery->clone()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('commission_amount'), 2),
        ];

        return Inertia::render('Affiliate/Commissions', [
            'business' => $business,
            'commissions' => $commissions,
            'summaryStats' => $summaryStats,
            'filters' => [
                'status' => $request->status ?? 'all',
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
            ],
        ]);
    }

    public function qrCodes(Request $request)
    {
        $user = $request->user();
        $business = $user->affiliateBusiness;

        $qrCodes = $business->qrCodes()
            ->with('location')
            ->withCount('customerScans')
            ->latest()
            ->get()
            ->map(function ($qr) {
                $qr->bookings_count = $qr->customerScans()
                    ->where('booking_completed', true)
                    ->count();
                $qr->revenue = AffiliateCommission::where('business_id', $qr->business_id)
                    ->whereHas('customerScan', fn ($q) => $q->where('qr_code_id', $qr->id))
                    ->sum('commission_amount');
                return $qr;
            });

        $locations = $business->locations()
            ->select('id', 'name', 'address_line_1', 'city', 'state', 'country', 'postal_code', 'latitude', 'longitude')
            ->get();

        return Inertia::render('Affiliate/QrCodes', [
            'business' => $business,
            'qrCodes' => $qrCodes,
            'locations' => $locations,
        ]);
    }

    public function settings(Request $request)
    {
        $user = $request->user();
        $business = $user->affiliateBusiness;

        $globalSettings = AffiliateGlobalSetting::first();

        return Inertia::render('Affiliate/Settings', [
            'business' => $business,
            'user' => $user->only('id', 'first_name', 'last_name', 'email'),
            'commissionTerms' => [
                'commission_rate' => $globalSettings->global_commission_rate ?? 3,
                'payout_threshold' => $globalSettings->global_payout_threshold ?? 50,
            ],
        ]);
    }

    public function updateSettings(Request $request)
    {
        $user = $request->user();
        $business = $user->affiliateBusiness;

        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'business_type' => 'required|string|max:100',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'nullable|string|max:30',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'legal_address' => 'nullable|string|max:500',
        ]);

        $business->update([
            'name' => $validated['business_name'],
            'business_type' => $validated['business_type'],
            'contact_email' => $validated['contact_email'],
            'contact_phone' => $validated['contact_phone'],
            'city' => $validated['city'],
            'country' => $validated['country'],
            'legal_address' => $validated['legal_address'],
        ]);

        return back()->with('success', 'Business information updated.');
    }

    public function updateBankDetails(Request $request)
    {
        $user = $request->user();
        $business = $user->affiliateBusiness;

        $validated = $request->validate([
            'bank_name' => 'nullable|string|max:100',
            'bank_iban' => 'nullable|string|max:50',
            'bank_bic' => 'nullable|string|max:20',
            'bank_account_name' => 'nullable|string|max:200',
            'payout_currency' => 'nullable|string|in:EUR,GBP,USD,CHF,SEK,NOK,DKK,PLN,CZK,HUF,TRY',
        ]);

        // Map payout_currency to the currency column
        if (isset($validated['payout_currency'])) {
            $validated['currency'] = $validated['payout_currency'];
            unset($validated['payout_currency']);
        }

        $business->update($validated);

        return back()->with('success', 'Bank details updated.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->update(['password' => Hash::make($validated['password'])]);

        return back()->with('success', 'Password updated.');
    }

    public function createQrCode(Request $request)
    {
        $user = $request->user();
        $business = $user->affiliateBusiness;

        if ($business->verification_status !== 'verified') {
            return back()->with('error', 'Your account must be approved before creating QR codes.');
        }

        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'location_id' => 'required',
            'location_name' => 'required_if:location_id,new|nullable|string|max:255',
            'address_line_1' => 'required_if:location_id,new|nullable|string|max:255',
            'city' => 'required_if:location_id,new|nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'required_if:location_id,new|nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'latitude' => 'required_if:location_id,new|nullable|numeric|between:-90,90',
            'longitude' => 'required_if:location_id,new|nullable|numeric|between:-180,180',
        ]);

        $locationData = [
            'label' => $validated['label'],
        ];

        if ($validated['location_id'] === 'new') {
            $locationCode = 'LOC-' . strtoupper(Str::random(8));

            $location = $business->locations()->create([
                'uuid' => Str::uuid(),
                'location_code' => $locationCode,
                'name' => $validated['location_name'],
                'address_line_1' => $validated['address_line_1'],
                'city' => $validated['city'],
                'state' => $validated['state'] ?? null,
                'country' => $validated['country'],
                'postal_code' => $validated['postal_code'] ?? '',
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
            ]);
            $locationData['location_id'] = $location->id;
            $locationData['location_name'] = $location->name;
        } else {
            $location = $business->locations()->findOrFail($validated['location_id']);
            $locationData['location_id'] = $location->id;
            $locationData['location_name'] = $location->name;
        }

        $qrCodeService = app(AffiliateQrCodeService::class);
        $qrCodeService->generateQrCode($business, $locationData);

        return back()->with('success', 'QR code created successfully!');
    }
}
