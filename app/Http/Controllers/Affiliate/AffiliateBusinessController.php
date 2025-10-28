<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\Affiliate\AffiliateBusiness;
use App\Models\Affiliate\AffiliateBusinessModel;
use App\Models\Affiliate\AffiliateDashboardSession;
use App\Models\Affiliate\AffiliateGlobalSetting;
use App\Notifications\Affiliate\BusinessRegistrationNotification;
use App\Notifications\Affiliate\NewBusinessRegistrationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AffiliateBusinessController extends Controller
{
    /**
     * Show the business registration form.
     */
    public function create()
    {
        return Inertia::render('Affiliate/Business/Register');
    }

    /**
     * Store a newly registered business.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'business_type' => 'required|in:hotel,hotel_chain,travel_agent,partner,corporate',
            'name' => 'required|string|max:255',
            'contact_email' => 'required|email|unique:affiliate_businesses,contact_email',
            'contact_phone' => 'required|string|max:50',
            'website' => 'nullable|url|max:255',
            'legal_address' => 'required|string|max:1000',
            'billing_address' => 'nullable|string|max:1000',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'currency' => 'required|string|max:3',
            'business_registration_number' => 'nullable|string|max:100|unique:affiliate_businesses,business_registration_number',
            'tax_id' => 'nullable|string|max:50|unique:affiliate_businesses,tax_id',
            'description' => 'nullable|string|max:2000',
            'accept_terms' => 'required|accepted',
        ]);

        // Generate dashboard access token
        $dashboardToken = $this->generateDashboardToken();

        // Generate verification token
        $verificationToken = $this->generateVerificationToken();

        // Create the business
        $business = AffiliateBusiness::create([
            'uuid' => Str::uuid(),
            'business_type' => $validated['business_type'],
            'name' => $validated['name'],
            'contact_email' => $validated['contact_email'],
            'contact_phone' => $validated['contact_phone'],
            'website' => $validated['website'],
            'legal_address' => $validated['legal_address'],
            'billing_address' => $validated['billing_address'],
            'city' => $validated['city'],
            'state' => $validated['state'],
            'country' => $validated['country'],
            'postal_code' => $validated['postal_code'],
            'currency' => $validated['currency'],
            'business_registration_number' => $validated['business_registration_number'],
            'tax_id' => $validated['tax_id'],
            'verification_token' => $verificationToken,
            'dashboard_access_token' => $dashboardToken,
            'dashboard_token_expires_at' => now()->addDays(30),
            'verification_status' => 'pending',
            'status' => 'pending',
        ]);

        // Create business model with default settings from global settings
        $globalSettings = AffiliateGlobalSetting::first();
        AffiliateBusinessModel::create([
            'uuid' => Str::uuid(),
            'business_id' => $business->id,
            'configured_at' => now(),
        ]);

        // Send verification email to business
        $business->notify(new BusinessRegistrationNotification($business));

        // Send notification to admin about new business registration
        $adminEmail = env('VITE_ADMIN_EMAIL', 'admin@vrooem.com');
        \Illuminate\Support\Facades\Notification::route('mail', $adminEmail)
            ->notify(new NewBusinessRegistrationNotification($business));

        return Inertia::render('Affiliate/Business/Success', [
            'business' => $business,
        ]);
    }

    /**
     * Show the affiliate dashboard.
     */
    public function dashboard($token)
    {
        // Get the full token from the URL (same logic as debugToken)
        $fullToken = request()->segment(count(request()->segments()));
        if ($fullToken === $token && strlen($token) < 20) {
            $fullToken = request()->route('token') ?? $token;
        }

        // Find business by dashboard token
        $business = AffiliateBusiness::where('dashboard_access_token', $fullToken)->first();

        if (!$business) {
            abort(403, 'Invalid or expired dashboard access token');
        }

        // For debugging: Log the validation check
        \Log::info('Dashboard access attempt', [
            'business_id' => $business->id,
            'business_name' => $business->name,
            'token' => $fullToken,
            'route_token' => $token,
            'token_expires_at' => $business->dashboard_token_expires_at,
            'is_valid' => $business->isDashboardTokenValid(),
            'status' => $business->status,
            'verification_status' => $business->verification_status,
        ]);

        // Check token validity
        if (!$business->isDashboardTokenValid()) {
            abort(403, 'Dashboard access token has expired');
        }

        // Check business status - only active businesses can access dashboard
        if ($business->status !== 'active') {
            return Inertia::render('Affiliate/Business/StatusPending', [
                'business' => $business,
            ]);
        }

        // Update last accessed time
        $business->update([
            'last_dashboard_access' => now(),
        ]);

        // Get business data
        $businessModel = $business->getEffectiveBusinessModel();

        // Get QR codes with proper relationship loading and commission calculations
        $qrCodes = $business->qrCodes()
            ->with(['location', 'commissions' => function($query) {
                $query->where('status', '!=', 'cancelled');
            }])
            ->latest()
            ->get();

        // Calculate commission earned per QR code
        $qrCodes->each(function($qrCode) {
            $totalCommission = $qrCode->commissions->sum('net_commission');
            $qrCode->total_commission_earned = (float) $totalCommission;
        });

        // Get customer scans and commissions statistics
        $totalScans = $business->customerScans()->count();
        $totalCommissions = (float) $business->commissions()->where('status', '!=', 'cancelled')->sum('net_commission');
        $pendingCommissions = (float) $business->commissions()->pending()->sum('net_commission');

        return Inertia::render('Affiliate/Dashboard/Index', [
            'business' => $business,
            'businessModel' => $businessModel,
            'qrCodes' => $qrCodes,
            'totalScans' => $totalScans,
            'totalCommissions' => $totalCommissions,
            'pendingCommissions' => $pendingCommissions,
        ]);
    }

    /**
     * Refresh the dashboard token.
     */
    public function refreshToken($token)
    {
        $business = AffiliateBusiness::where('dashboard_access_token', $token)->first();

        if (!$business || !$business->isDashboardTokenValid()) {
            return response()->json(['error' => 'Invalid or expired token'], 403);
        }

        $newToken = $business->refreshDashboardToken();

        return response()->json([
            'token' => $newToken,
            'expires_at' => $business->dashboard_token_expires_at,
        ]);
    }

    /**
     * Logout from dashboard.
     */
    public function logout($token)
    {
        $session = AffiliateDashboardSession::findByToken($token);

        if ($session) {
            $session->revoke('User logout');
        }

        return Inertia::render('Affiliate/Business/Logout');
    }

    /**
     * Verify business email.
     */
    public function verifyEmail($token)
    {
        // Get the full token from the URL (same logic as dashboard method)
        $fullToken = request()->segment(count(request()->segments()));
        if ($fullToken === $token && strlen($token) < 20) {
            $fullToken = request()->route('token') ?? $token;
        }

        // Log for debugging
        \Log::info('Email verification attempt', [
            'original_token' => $token,
            'full_token' => $fullToken,
            'route_token' => request()->route('token'),
            'all_segments' => request()->segments(),
        ]);

        $business = AffiliateBusiness::where('verification_token', $fullToken)->first();

        if (!$business) {
            // Also try with the original token for backward compatibility
            $business = AffiliateBusiness::where('verification_token', $token)->first();

            if (!$business) {
                \Log::warning('Email verification failed - token not found', [
                    'searched_token' => $fullToken,
                    'original_token' => $token,
                    'all_tokens_in_db' => AffiliateBusiness::whereNotNull('verification_token')->pluck('verification_token')->take(5)->toArray()
                ]);

                return Inertia::render('Affiliate/Business/VerificationError', [
                    'message' => 'Invalid verification token'
                ]);
            }
        }

        $business->update([
            'verification_status' => 'verified',
            'verified_at' => now(),
            'verification_token' => null,
            'status' => 'active',
        ]);

        return Inertia::render('Affiliate/Business/Verified', [
            'business' => $business,
        ]);
    }

    /**
     * Generate a secure dashboard access token.
     */
    private function generateDashboardToken(): string
    {
        do {
            $token = 'AFF-' . strtoupper(Str::random(12)) . '-' . bin2hex(random_bytes(16));
        } while (AffiliateBusiness::where('dashboard_access_token', $token)->exists());

        return $token;
    }

    /**
     * Generate a secure verification token.
     */
    private function generateVerificationToken(): string
    {
        do {
            $token = 'VER-' . strtoupper(Str::random(16)) . '-' . bin2hex(random_bytes(8));
        } while (AffiliateBusiness::where('verification_token', $token)->exists());

        return $token;
    }

    /**
     * Check if business email already exists.
     */
    public function checkEmail(Request $request)
    {
        // Validate email format using Laravel's built-in validation
        $validator = Validator::make($request->all(), [
            'email' => 'required|email:rfc,dns',
        ]);

        if ($validator->fails()) {
            return response()->json(['exists' => false, 'valid' => false]);
        }

        $exists = AffiliateBusiness::where('contact_email', $request->email)->exists();

        return response()->json(['exists' => $exists, 'valid' => true]);
    }

    /**
     * Get business statistics for admin.
     */
    public function statistics()
    {
        $totalBusinesses = AffiliateBusiness::count();
        $activeBusinesses = AffiliateBusiness::active()->count();
        $pendingVerification = AffiliateBusiness::where('verification_status', 'pending')->count();
        $totalQrCodes = \App\Models\Affiliate\AffiliateQrCode::count();
        $totalScans = \App\Models\Affiliate\AffiliateCustomerScan::count();
        $totalCommissions = \App\Models\Affiliate\AffiliateCommission::sum('net_commission');

        return response()->json([
            'total_businesses' => $totalBusinesses,
            'active_businesses' => $activeBusinesses,
            'pending_verification' => $pendingVerification,
            'total_qr_codes' => $totalQrCodes,
            'total_scans' => $totalScans,
            'total_commissions' => $totalCommissions,
        ]);
    }

    /**
     * Debug business token status (for testing).
     */
    public function debugToken($token)
    {
        // The route might have captured only part of the token, so let's get the full token from the URL
        $fullToken = request()->segment(count(request()->segments())); // Get the last segment
        if ($fullToken === $token && strlen($token) < 20) {
            // If the token is too short, it's probably just the locale, get the real token
            $fullToken = request()->route('token') ?? $token;
        }

        // First, let's see if we can find any business with any token
        $allBusinesses = AffiliateBusiness::all();
        $foundBusiness = null;

        foreach ($allBusinesses as $business) {
            if ($business->dashboard_access_token === $fullToken) {
                $foundBusiness = $business;
                break;
            }
        }

        if (!$foundBusiness) {
            return response()->json([
                'error' => 'Business not found',
                'token_searched' => $fullToken,
                'route_token' => $token,
                'all_segments' => request()->segments(),
                'total_businesses' => $allBusinesses->count(),
                'all_tokens' => $allBusinesses->pluck('dashboard_access_token')->filter()->toArray()
            ], 404);
        }

        return response()->json([
            'business_id' => $foundBusiness->id,
            'name' => $foundBusiness->name,
            'status' => $foundBusiness->status,
            'verification_status' => $foundBusiness->verification_status,
            'verified_at' => $foundBusiness->verified_at,
            'dashboard_token' => $foundBusiness->dashboard_access_token,
            'dashboard_token_expires_at' => $foundBusiness->dashboard_token_expires_at,
            'last_dashboard_access' => $foundBusiness->last_dashboard_access,
            'is_token_valid' => $foundBusiness->isDashboardTokenValid(),
            'is_expired' => $foundBusiness->dashboard_token_expires_at ? $foundBusiness->dashboard_token_expires_at->isPast() : 'No expiry set',
            'expiry_status' => $foundBusiness->dashboard_token_expires_at ?
                ($foundBusiness->dashboard_token_expires_at->isFuture() ? 'Valid' : 'Expired') : 'No expiry set',
            'current_time' => now(),
        ]);
    }

    /**
     * Create a new location for the business.
     */
    public function createLocation(Request $request, $token)
    {
        // Get the full token from the URL (same logic as debugToken)
        $fullToken = request()->segment(count(request()->segments()));
        if ($fullToken === $token && strlen($token) < 20) {
            $fullToken = request()->route('token') ?? $token;
        }

        $business = AffiliateBusiness::where('dashboard_access_token', $fullToken)->first();

        if (!$business) {
            return response()->json([
                'error' => 'Business not found',
                'token_searched' => $fullToken,
                'route_token' => $token,
                'all_segments' => request()->segments()
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'location_code' => 'required|string|max:50',
        ]);

        try {
            $location = $business->locations()->create([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'location_code' => $validated['location_code'],
                'name' => $validated['name'],
                'description' => null,
                'address_line_1' => $validated['address_line_1'],
                'address_line_2' => $validated['address_line_2'],
                'city' => $validated['city'],
                'state' => $validated['state'],
                'country' => $validated['country'],
                'postal_code' => $validated['postal_code'],
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'location_accuracy_radius' => 50,
                'location_email' => $business->contact_email,
                'location_phone' => $business->contact_phone,
                'manager_name' => null,
                'timezone' => 'UTC',
                'operating_hours' => null,
                'qr_code_url' => null,
                'qr_code_image_path' => null,
                'qr_code_generated_at' => null,
                'verification_status' => 'verified',
                'is_active' => true,
                'verified_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Location created successfully',
                'location' => $location,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to create location: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create location. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Manually verify and activate a business (for testing/admin use).
     */
    public function manualVerify($token)
    {
        // Get the full token from the URL (same logic as debugToken)
        $fullToken = request()->segment(count(request()->segments()));
        if ($fullToken === $token && strlen($token) < 20) {
            $fullToken = request()->route('token') ?? $token;
        }

        $business = AffiliateBusiness::where('dashboard_access_token', $fullToken)->first();

        if (!$business) {
            return response()->json([
                'error' => 'Business not found',
                'token_searched' => $fullToken,
                'route_token' => $token,
                'all_segments' => request()->segments()
            ], 404);
        }

        $business->update([
            'verification_status' => 'verified',
            'verified_at' => now(),
            'verification_token' => null,
            'status' => 'active',
            'dashboard_token_expires_at' => now()->addDays(30), // Refresh token expiry
            'last_dashboard_access' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Business has been manually verified and activated',
            'business_id' => $business->id,
            'business_name' => $business->name,
            'token_expires_at' => $business->dashboard_token_expires_at,
            'new_status' => $business->fresh()->isDashboardTokenValid(),
        ]);
    }
}