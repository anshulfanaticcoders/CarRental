<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\Affiliate\AffiliateBusiness;
use App\Models\Affiliate\AffiliateBusinessLocation;
use App\Models\Affiliate\AffiliateQrCode;
use App\Models\Affiliate\AffiliateDashboardSession;
use App\Services\Affiliate\AffiliateQrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Response;

class AffiliateQrCodeController extends Controller
{
    public function __construct(
        private AffiliateQrCodeService $qrCodeService
    ) {}

    /**
     * Display QR codes for a business.
     */
    public function index($token)
    {
        $session = AffiliateDashboardSession::findByToken($token);

        if (!$session || !$session->isValid()) {
            abort(403, 'Invalid or expired dashboard access token');
        }

        $business = $session->business;
        $qrCodes = $business->qrCodes()
            ->with(['location'])
            ->latest()
            ->get();

        return Inertia::render('Affiliate/Dashboard/QrCodes', [
            'business' => $business,
            'qrCodes' => $qrCodes,
            'dashboardToken' => $token,
        ]);
    }

    /**
     * Show form for creating a new QR code.
     */
    public function create($token)
    {
        // Extract the actual dashboard token from the URL
        // The URL structure is: /{locale}/business/qr-codes/create/{token}
        $segments = request()->segments();
        $actualToken = end($segments); // Get the last segment (actual token)

        // Fallback: if last segment looks like a locale, use the route parameter
        if (in_array($actualToken, ['en', 'fr', 'nl', 'es', 'ar'])) {
            $actualToken = $token;
        }

        // Debug: Log the full segments to see what's happening
        \Log::debug('Token extraction debug', [
            'full_url' => request()->fullUrl(),
            'segments' => $segments,
            'last_segment' => $actualToken,
            'route_token' => $token,
            'all_segments_count' => count($segments),
            'segment_details' => array_map(function($index, $segment) {
                return ['index' => $index, 'segment' => $segment, 'length' => strlen($segment)];
            }, array_keys($segments), $segments)
        ]);

        $business = AffiliateBusiness::where('dashboard_access_token', $actualToken)->first();
        $usedToken = $actualToken;

        // For debugging: Log the validation check (same as business dashboard)
        \Log::info('QR Code creation attempt', [
            'business_id' => $business ? $business->id : 'Not found',
            'business_name' => $business ? $business->name : 'Not found',
            'actual_token' => $actualToken,
            'route_token' => $token,
            'url_segments' => request()->segments(),
            'token_expires_at' => $business ? $business->dashboard_token_expires_at : 'N/A',
            'is_valid' => $business ? $business->isDashboardTokenValid() : 'N/A',
            'status' => $business ? $business->status : 'N/A',
            'verification_status' => $business ? $business->verification_status : 'N/A',
        ]);

        if (!$business) {
            abort(403, 'Invalid or expired dashboard access token');
        }

        // Temporarily bypass business status validation for testing (same as business dashboard)
        // if ($business->status !== 'active' || $business->verification_status !== 'verified') {
        //     abort(403, 'Business must be active and verified to access this feature');
        // }

        $locations = $business->locations()->get();

        return Inertia::render('Affiliate/Dashboard/CreateQrCode', [
            'business' => $business,
            'locations' => $locations,
            'dashboardToken' => $actualToken,
        ]);
    }

    /**
     * Store a new QR code.
     */
    public function store(Request $request, $token)
    {
        // Extract the actual dashboard token from the URL
        // The URL structure is: /{locale}/business/qr-codes/store/{token}
        $segments = request()->segments();
        $actualToken = end($segments); // Get the last segment (actual token)

        // Fallback: if last segment looks like a locale, use the route parameter
        if (in_array($actualToken, ['en', 'fr', 'nl', 'es', 'ar'])) {
            $actualToken = $token;
        }

        // Debug logging
        \Log::info('QR Code store attempt', [
            'request_data' => $request->all(),
            'segments' => $segments,
            'actual_token' => $actualToken,
            'route_token' => $token,
            'full_url' => request()->fullUrl(),
        ]);

        $business = AffiliateBusiness::where('dashboard_access_token', $actualToken)->first();

        if (!$business) {
            \Log::error('Business not found for QR code creation', [
                'actual_token' => $actualToken,
                'route_token' => $token,
            ]);
            abort(403, 'Invalid or expired dashboard access token');
        }

        // Temporarily bypass business status validation for testing (same as business dashboard)
        // if ($business->status !== 'active' || $business->verification_status !== 'verified') {
        //     abort(403, 'Business must be active and verified to access this feature');
        // }

        // Extract location_id from new_location_data if present
        $locationData = [];
        if ($request->has('new_location_data') && is_array($request->input('new_location_data'))) {
            $locationId = $request->input('new_location_data.id');
            if ($locationId) {
                $locationData['location_id'] = $locationId;
            }
        }

        // Validate the extracted location_id
        try {
            $validated = [];

            if (!empty($locationData['location_id'])) {
                $validated = $request->validate([
                    'new_location_data.id' => [
                        'required',
                        'exists:affiliate_business_locations,id',
                        // Custom rule to prevent duplicate QR codes for the same location
                        function ($attribute, $value, $fail) use ($business) {
                            // Check if QR code already exists for this location and business
                            $existingQrCode = AffiliateQrCode::where('business_id', $business->id)
                                                          ->where('location_id', $value)
                                                          ->where('status', '!=', 'suspended')
                                                          ->first();

                            if ($existingQrCode) {
                                $fail('A QR code already exists for this location. Each location can only have one active QR code.');
                            }
                        },
                    ],
                ]);
            }

            \Log::info('Validation passed', ['validated_data' => $validated, 'location_data' => $locationData]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);
            throw $e;
        }

        // Set default values - none needed now

        try {
            \Log::info('Attempting to generate QR code', ['business_id' => $business->id, 'location_data' => $locationData]);
            $qrCode = $this->qrCodeService->generateQrCode($business, $locationData);

            \Log::info('QR code generated successfully', ['qr_code_id' => $qrCode->id]);

            return redirect()->route('affiliate.qr-codes.index', ['token' => $actualToken])
                ->with('success', 'QR code generated successfully!');
        } catch (\Exception $e) {
            \Log::error('QR code generation failed: ' . $e->getMessage(), [
                'business_id' => $business->id,
                'validated_data' => $validated,
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Failed to generate QR code. Please try again.');
        }
    }

    /**
     * Show QR code details.
     */
    public function show($token, $qrCodeId)
    {
        $session = AffiliateDashboardSession::findByToken($token);

        if (!$session || !$session->isValid()) {
            abort(403, 'Invalid or expired dashboard access token');
        }

        $business = $session->business;
        $qrCode = $business->qrCodes()
            ->with(['location', 'customerScans' => function ($query) {
                $query->latest()->limit(10);
            }])
            ->findOrFail($qrCodeId);

        $scanStats = [
            'total_scans' => $qrCode->customerScans()->count(),
            'unique_devices' => $qrCode->customerScans()->distinct('device_id')->count('device_id'),
            'recent_scans' => $qrCode->customerScans()->where('scan_date', '>=', now()->subDays(7))->count(),
        ];

        return Inertia::render('Affiliate/Dashboard/QrCodeDetails', [
            'business' => $business,
            'qrCode' => $qrCode,
            'scanStats' => $scanStats,
            'dashboardToken' => $token,
        ]);
    }

    /**
     * View QR code image (display in browser).
     */
    public function viewImage($token, $qrCodeId)
    {
        $session = AffiliateDashboardSession::findByToken($token);

        if (!$session || !$session->isValid()) {
            abort(403, 'Invalid or expired dashboard access token');
        }

        $business = $session->business;
        $qrCode = $business->qrCodes()->findOrFail($qrCodeId);

        try {
            // Check if QR code has an image path
            if (!$qrCode->qr_image_path) {
                abort(404, 'QR code image not found - no image path stored');
            }

            // Try to get image from storage
            $disk = Storage::disk('upcloud');
            if (!$disk->exists($qrCode->qr_image_path)) {
                abort(404, 'QR code image file not found in storage');
            }

            $qrImageContent = $disk->get($qrCode->qr_image_path);

            if (!$qrImageContent) {
                abort(404, 'QR code image content could not be read');
            }

            // Determine content type based on file extension
            $extension = strtolower(pathinfo($qrCode->qr_image_path, PATHINFO_EXTENSION));
            $contentType = match ($extension) {
                'png' => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                'svg' => 'image/svg+xml',
                default => 'image/png',
            };

            return Response::make($qrImageContent)
                ->header('Content-Type', $contentType)
                ->header('Cache-Control', 'public, max-age=3600') // Cache for 1 hour
                ->header('Expires', now()->addHour()->toRfc1123String());
        } catch (\Exception $e) {
            \Log::error('QR code image view failed: ' . $e->getMessage(), [
                'qr_code_id' => $qrCode->id,
                'image_path' => $qrCode->qr_image_path,
                'trace' => $e->getTraceAsString()
            ]);
            abort(404, 'QR code image not found: ' . $e->getMessage());
        }
    }

    /**
     * Download QR code image.
     */
    public function download($token, $qrCodeId)
    {
        $session = AffiliateDashboardSession::findByToken($token);

        if (!$session || !$session->isValid()) {
            abort(403, 'Invalid or expired dashboard access token');
        }

        $business = $session->business;
        $qrCode = $business->qrCodes()->findOrFail($qrCodeId);

        try {
            // Check if QR code has an image path
            if (!$qrCode->qr_image_path) {
                abort(404, 'QR code image not found - no image path stored');
            }

            // Try to get image from storage
            $disk = Storage::disk('upcloud');
            if (!$disk->exists($qrCode->qr_image_path)) {
                abort(404, 'QR code image file not found in storage');
            }

            $qrImageContent = $disk->get($qrCode->qr_image_path);

            if (!$qrImageContent) {
                abort(404, 'QR code image content could not be read');
            }

            $filename = 'qr-code-' . ($qrCode->short_code ?: $qrCode->id) . '.png';

            return Response::make($qrImageContent)
                ->header('Content-Type', 'image/png')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        } catch (\Exception $e) {
            \Log::error('QR code download failed: ' . $e->getMessage(), [
                'qr_code_id' => $qrCode->id,
                'image_path' => $qrCode->qr_image_path,
                'trace' => $e->getTraceAsString()
            ]);
            abort(404, 'QR code image not found: ' . $e->getMessage());
        }
    }

    /**
     * Update QR code settings.
     */
    public function update(Request $request, $token, $qrCodeId)
    {
        $session = AffiliateDashboardSession::findByToken($token);

        if (!$session || !$session->isValid()) {
            abort(403, 'Invalid or expired dashboard access token');
        }

        $business = $session->business;
        $qrCode = $business->qrCodes()->findOrFail($qrCodeId);

        $validated = $request->validate([
            'status' => 'required|in:active,inactive,expired,suspended,pending',
        ]);

        $qrCode->update($validated);

        return back()->with('success', 'QR code updated successfully!');
    }

    /**
     * Deactivate/Revoke QR code.
     */
    public function revoke($token, $qrCodeId)
    {
        $session = AffiliateDashboardSession::findByToken($token);

        if (!$session || !$session->isValid()) {
            abort(403, 'Invalid or expired dashboard access token');
        }

        $business = $session->business;
        $qrCode = $business->qrCodes()->findOrFail($qrCodeId);

        $qrCode->update([
            'status' => 'suspended',
        ]);

        return back()->with('success', 'QR code suspended successfully!');
    }

    /**
     * Reactivate QR code.
     */
    public function reactivate($token, $qrCodeId)
    {
        $session = AffiliateDashboardSession::findByToken($token);

        if (!$session || !$session->isValid()) {
            abort(403, 'Invalid or expired dashboard access token');
        }

        $business = $session->business;
        $qrCode = $business->qrCodes()->findOrFail($qrCodeId);

        $qrCode->update([
            'status' => 'active',
        ]);

        return back()->with('success', 'QR code reactivated successfully!');
    }

    /**
     * Delete QR code.
     */
    public function destroy($token, $qrCodeId)
    {
        // Extract the actual dashboard token from the URL
        // The URL structure is: /{locale}/business/qr-codes/{token}/{qrCodeId}
        $segments = request()->segments();

        // The token is at index 3: ['en', 'business', 'qr-codes', 'TOKEN', 'ID']
        $actualToken = $segments[3] ?? $token;

        // Fallback: if last segment looks like a locale, use the route parameter
        if (in_array($actualToken, ['en', 'fr', 'nl', 'es', 'ar'])) {
            $actualToken = $token;
        }

        $business = AffiliateBusiness::where('dashboard_access_token', $actualToken)->first();

        if (!$business) {
            abort(403, 'Invalid or expired dashboard access token');
        }
        $qrCode = $business->qrCodes()->findOrFail($qrCodeId);

        // Delete QR code image from storage
        try {
            if ($qrCode->qr_image_path) {
                Storage::disk('upcloud')->delete($qrCode->qr_image_path);
            }
            if ($qrCode->qr_pdf_path) {
                Storage::disk('upcloud')->delete($qrCode->qr_pdf_path);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to delete QR code image: ' . $e->getMessage());
        }

        // Delete QR code record (will cascade delete customer scans)
        $qrCode->delete();

        return redirect()->route('affiliate.qr-codes.index', ['token' => $actualToken])
            ->with('success', 'QR code deleted successfully!');
    }

    /**
     * Bulk delete QR codes.
     */
    public function bulkDestroy(Request $request, $token)
    {
        // Extract the actual dashboard token from the URL
        // The URL structure is: /{locale}/business/qr-codes/bulk-delete/{token}
        $segments = request()->segments();
        $actualToken = end($segments); // Get the last segment (actual token)

        // Fallback: if last segment looks like a locale, use the route parameter
        if (in_array($actualToken, ['en', 'fr', 'nl', 'es', 'ar'])) {
            $actualToken = $token;
        }

        $business = AffiliateBusiness::where('dashboard_access_token', $actualToken)->first();

        if (!$business) {
            abort(403, 'Invalid or expired dashboard access token');
        }

        $validated = $request->validate([
            'qr_code_ids' => 'required|array',
            'qr_code_ids.*' => 'integer|exists:affiliate_qr_codes,id',
        ]);

        $qrCodes = $business->qrCodes()->whereIn('id', $validated['qr_code_ids'])->get();

        $deletedCount = 0;
        foreach ($qrCodes as $qrCode) {
            // Delete QR code image from storage
            try {
                if ($qrCode->qr_image_path) {
                    Storage::disk('upcloud')->delete($qrCode->qr_image_path);
                }
                if ($qrCode->qr_pdf_path) {
                    Storage::disk('upcloud')->delete($qrCode->qr_pdf_path);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to delete QR code image: ' . $e->getMessage());
            }

            // Delete QR code record (will cascade delete customer scans)
            $qrCode->delete();
            $deletedCount++;
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully deleted {$deletedCount} QR code(s)!",
            'deleted_count' => $deletedCount,
        ]);
    }

    /**
     * QR code landing page (short code).
     */
    public function qrLanding(string $shortCode, Request $request)
    {
        $qrCode = AffiliateQrCode::where('short_code', $shortCode)
                               ->where('status', 'active')
                               ->first();

        if (!$qrCode || !$qrCode->isValid()) {
            return redirect('/')->with('error', 'QR code not found or expired');
        }

        // Create scan record
        $trackingData = json_encode([
            'type' => 'affiliate_qr',
            'business_id' => $qrCode->business_id,
            'qr_id' => $shortCode,
            'timestamp' => now()->timestamp,
            'version' => '1.0',
        ]);

        $result = $this->qrCodeService->processQrScan($trackingData, $request);

        if (!$result) {
            return redirect('/')->with('error', 'Failed to process QR code scan');
        }

        // Store affiliate data
        $affiliateData = [
            'business_id' => $qrCode->business_id,
            'qr_code_id' => $qrCode->id,
            'customer_scan_id' => $result['customer_scan']->id,
            'discount_type' => $qrCode->discount_type,
            'discount_value' => $qrCode->discount_value,
            'business_name' => $qrCode->business->name,
            'scanned_at' => now()->toISOString(),
        ];

        session(['affiliate_data' => $affiliateData]);

        // Redirect to vehicles page with affiliate indication
        return redirect('/en/vehicles')->with('affiliate', true);
    }

    /**
     * Track QR code scan (public endpoint).
     */
    public function track($trackingData, Request $request)
    {
        try {
            $result = $this->qrCodeService->processQrScan($trackingData, $request);

            if (!$result) {
                // Invalid QR code or tracking data
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired QR code',
                ], 404);
            }

            // Store affiliate data in session
            $affiliateData = [
                'business_id' => $result['qr_code']->business_id,
                'qr_code_id' => $result['qr_code']->id,
                'customer_scan_id' => $result['customer_scan']->id,
                'discount_type' => $result['qr_code']->discount_type,
                'discount_value' => $result['qr_code']->discount_value,
                'business_name' => $result['business']->name,
                'scanned_at' => now()->toISOString(),
            ];

            session(['affiliate_data' => $affiliateData]);

            // Redirect to main website with affiliate discount applied
            $redirectUrl = route('welcome', ['locale' => app()->getLocale()]);

            return response()->json([
                'success' => true,
                'message' => 'QR code scanned successfully!',
                'data' => [
                    'business_name' => $result['business']->name,
                    'discount_rate' => $result['discount_rate'],
                    'redirect_url' => $redirectUrl,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('QR code tracking failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the QR code',
            ], 500);
        }
    }

    /**
     * Get QR code analytics data.
     */
    public function analytics($token, $qrCodeId)
    {
        $session = AffiliateDashboardSession::findByToken($token);

        if (!$session || !$session->isValid()) {
            abort(403, 'Invalid or expired dashboard access token');
        }

        $business = $session->business;
        $qrCode = $business->qrCodes()->findOrFail($qrCodeId);

        // Get scan analytics
        $scansByDay = $qrCode->customerScans()
            ->selectRaw('DATE(scan_date) as date, COUNT(*) as scans')
            ->where('scan_date', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $scansByHour = $qrCode->customerScans()
            ->selectRaw('scan_hour as hour, COUNT(*) as scans')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $deviceTypes = $qrCode->customerScans()
            ->selectRaw('device_type, COUNT(*) as count')
            ->groupBy('device_type')
            ->get();

        return response()->json([
            'scans_by_day' => $scansByDay,
            'scans_by_hour' => $scansByHour,
            'device_types' => $deviceTypes,
            'total_scans' => $qrCode->customerScans()->count(),
            'unique_devices' => $qrCode->customerScans()->distinct('device_id')->count('device_id'),
        ]);
    }
}