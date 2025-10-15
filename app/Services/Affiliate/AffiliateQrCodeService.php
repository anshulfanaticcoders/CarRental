<?php

namespace App\Services\Affiliate;

use App\Models\Affiliate\AffiliateBusiness;
use App\Models\Affiliate\AffiliateBusinessLocation;
use App\Models\Affiliate\AffiliateQrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Http\Request;

class AffiliateQrCodeService
{
    /**
     * Generate a unique QR code for an affiliate business.
     */
    public function generateQrCode(AffiliateBusiness $business, array $locationData = []): AffiliateQrCode
    {
        // Generate unique QR code data
        $qrData = $this->generateQrData($business, $locationData);

        // Generate QR code image
        $qrImage = $this->generateQrImage($qrData);

        // Upload to UpCloud storage
        $qrImageUrl = $this->uploadQrImage($qrImage, $business->id);

        // Generate unique QR code identifiers
        $qrCodeValue = uniqid('AFF-QR-', true);
        $qrHash = hash('sha256', $qrData);
        $shortCode = substr(strtoupper(str_replace(['+', '/', '='], '', base64_encode(random_bytes(8)))), 0, 12);
        $qrUrl = url('/affiliate/qr/' . $shortCode);

        // Get business model for discount configuration
        $businessModel = $business->getEffectiveBusinessModel();

        // Create database record
        return AffiliateQrCode::create([
            'uuid' => Str::uuid(),
            'business_id' => $business->id,
            'location_id' => $locationData['location_id'] ?? null,
            'qr_code_value' => $qrCodeValue,
            'qr_hash' => $qrHash,
            'short_code' => $shortCode,
            'qr_url' => $qrUrl,
            'qr_image_path' => $qrImageUrl,
            'discount_type' => $businessModel['discount_type'],
            'discount_value' => $businessModel['discount_value'],
            'min_booking_amount' => $businessModel['min_booking_amount'],
            'max_discount_amount' => $businessModel['max_discount_amount'],
            'status' => 'active',
        ]);
    }

    /**
     * Generate QR code data with tracking information.
     */
    private function generateQrData(AffiliateBusiness $business, array $locationData): string
    {
        $trackingData = [
            'type' => 'affiliate_qr',
            'business_id' => $business->id,
            'location_id' => $locationData['location_id'] ?? null,
            'qr_id' => uniqid('qr_', true),
            'timestamp' => now()->timestamp,
            'version' => '1.0',
        ];

        // Create a short, scannable URL that contains the tracking data (without locale for backward compatibility)
        $trackingUrl = url('/affiliate/track/' . $this->encodeTrackingData($trackingData));

        return $trackingUrl;
    }

    /**
     * Generate QR code image.
     */
    private function generateQrImage(string $data): string
    {
        try {
            // Generate high-resolution QR code with improved quality settings
            $qrCode = QrCode::format('png')
                ->size(800)           // Increased from 300 to 800 for high resolution
                ->errorCorrection('H') // Highest error correction for better scanning
                ->margin(4)           // Increased margin for better scanning
                ->encoding('UTF-8')
                ->generate($data);

            return $qrCode;
        } catch (\Exception $e) {
            // If PNG fails, try SVG format as fallback with high quality
            \Log::warning('PNG QR code generation failed, trying SVG fallback', [
                'error' => $e->getMessage(),
                'data_length' => strlen($data),
            ]);

            try {
                $qrCode = QrCode::format('svg')
                    ->size(800)           // High resolution SVG
                    ->errorCorrection('H') // Highest error correction
                    ->margin(4)           // Increased margin
                    ->encoding('UTF-8')
                    ->generate($data);

                return $qrCode;
            } catch (\Exception $svgException) {
                \Log::error('Both PNG and SVG QR code generation failed', [
                    'png_error' => $e->getMessage(),
                    'svg_error' => $svgException->getMessage(),
                ]);
                throw new \Exception('Unable to generate QR code: ' . $svgException->getMessage());
            }
        }
    }

    /**
     * Upload QR code image to UpCloud storage.
     */
    private function uploadQrImage(string $qrImage, string $businessId): string
    {
        // Determine file extension based on content
        $extension = str_starts_with($qrImage, '<?xml') ? 'svg' : 'png';
        $filename = 'affiliate/qr-codes/' . $businessId . '/' . uniqid('qr_') . '.' . $extension;

        // Store in UpCloud (assuming 'upcloud' is configured)
        Storage::disk('upcloud')->put($filename, $qrImage);

        \Log::info('QR code uploaded to storage', [
            'filename' => $filename,
            'extension' => $extension,
            'business_id' => $businessId,
            'size' => strlen($qrImage),
        ]);

        // Return the file path (not URL) for storage in qr_image_path field
        return $filename;
    }

    /**
     * Encode tracking data for URL.
     */
    private function encodeTrackingData(array $data): string
    {
        // Create a compact representation of tracking data
        $encoded = base64_encode(json_encode($data));
        return rtrim(strtr($encoded, '+/', '-_'), '='); // URL-safe base64
    }

    /**
     * Decode tracking data from URL.
     */
    public function decodeTrackingData(string $encodedData): ?array
    {
        try {
            // Add back padding that was removed
            $padded = str_pad($encodedData, strlen($encodedData) % 4, '=', STR_PAD_RIGHT);
            $decoded = base64_decode(strtr($padded, '-_', '+/'));

            if (!$decoded) {
                return null;
            }

            return json_decode($decoded, true);
        } catch (\Exception $e) {
            \Log::error('Failed to decode QR tracking data: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Process QR code scan and create customer scan record.
     */
    public function processQrScan(string $trackingData, Request $request): ?array
    {
        $decodedData = $this->decodeTrackingData($trackingData);

        if (!$decodedData) {
            return null;
        }

        // Find the QR code by hash first (most reliable)
        $qrHash = hash('sha256', $trackingData);
        $qrCode = AffiliateQrCode::where('qr_hash', $qrHash)->first();

        // If not found by hash, try to find by qr_id in tracking data
        if (!$qrCode && isset($decodedData['qr_id'])) {
            // Check if this qr_id matches any existing QR code's qr_hash
            $qrCode = AffiliateQrCode::where('qr_hash', hash('sha256', json_encode([
                'type' => 'affiliate_qr',
                'business_id' => $decodedData['business_id'],
                'location_id' => $decodedData['location_id'] ?? null,
                'qr_id' => $decodedData['qr_id'],
                'timestamp' => $decodedData['timestamp'],
                'version' => '1.0'
            ])))->first();
        }

        // If still not found, try to find by business and location combination
        if (!$qrCode && isset($decodedData['business_id'], $decodedData['location_id'])) {
            $qrCode = AffiliateQrCode::where('business_id', $decodedData['business_id'])
                                   ->where('location_id', $decodedData['location_id'])
                                   ->where('status', 'active')
                                   ->first();
        }

        if (!$qrCode || !$qrCode->isValid()) {
            return null;
        }

        // Get or create customer session
        $sessionToken = $this->getOrCreateCustomerSession($request);

        // Create customer scan record
        $customerScan = \App\Models\Affiliate\AffiliateCustomerScan::create([
            'uuid' => Str::uuid(),
            'qr_code_id' => $qrCode->id,
            'customer_id' => auth()->id(), // null if not logged in
            'session_id' => $sessionToken,
            'scan_token' => $this->generateScanToken(),
            'tracking_url' => url('/affiliate/track/' . $trackingData),
            'device_id' => $this->getDeviceId($request),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_type' => $this->detectDeviceType($request),
            'browser' => $this->detectBrowser($request),
            'platform' => $this->detectPlatform($request),
            'scan_date' => now()->toDateString(),
            'scan_hour' => now()->hour,
            'user_timezone' => $request->header('X-Timezone') ?? 'UTC',
            'scan_result' => 'success',
            'processing_time_ms' => 0, // Will be calculated
        ]);

        // Update QR code usage counts
        $qrCode->incrementUsage();

        // Store affiliate information in session for later use in booking
        $this->storeAffiliateDataInSession($qrCode, $customerScan);

        $businessModel = $qrCode->business->getEffectiveBusinessModel();

        return [
            'qr_code' => $qrCode,
            'customer_scan' => $customerScan,
            'business' => $qrCode->business,
            'discount_rate' => $businessModel['discount_value'],
            'discount_type' => $businessModel['discount_type'],
        ];
    }

    /**
     * Get or create customer session token.
     */
    private function getOrCreateCustomerSession(Request $request): string
    {
        $sessionKey = 'affiliate_session_token';

        if (session()->has($sessionKey)) {
            return session($sessionKey);
        }

        $sessionToken = 'AFF-' . strtoupper(uniqid()) . '-' . bin2hex(random_bytes(8));
        session([$sessionKey => $sessionToken]);

        return $sessionToken;
    }

    /**
     * Generate scan token.
     */
    private function generateScanToken(): string
    {
        return 'SCAN-' . strtoupper(uniqid()) . '-' . bin2hex(random_bytes(16));
    }

    /**
     * Get device identifier.
     */
    private function getDeviceId(Request $request): ?string
    {
        // Try to get a consistent device identifier
        return $request->header('X-Device-ID') ??
               $request->cookie('device_id') ??
               $this->generateDeviceId();
    }

    /**
     * Generate device ID.
     */
    private function generateDeviceId(): string
    {
        return 'DEVICE-' . uniqid() . '-' . bin2hex(random_bytes(8));
    }

    /**
     * Detect device type.
     */
    private function detectDeviceType(Request $request): string
    {
        $userAgent = $request->userAgent();

        if (preg_match('/Mobile|Android|iPhone|iPad|iPod/', $userAgent)) {
            if (preg_match('/iPad/', $userAgent)) {
                return 'tablet';
            }
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Detect browser.
     */
    private function detectBrowser(Request $request): string
    {
        $userAgent = $request->userAgent();

        if (preg_match('/Chrome/', $userAgent)) {
            return 'Chrome';
        } elseif (preg_match('/Firefox/', $userAgent)) {
            return 'Firefox';
        } elseif (preg_match('/Safari/', $userAgent)) {
            return 'Safari';
        } elseif (preg_match('/Edge/', $userAgent)) {
            return 'Edge';
        }

        return 'Unknown';
    }

    /**
     * Detect platform/OS.
     */
    private function detectPlatform(Request $request): string
    {
        $userAgent = $request->userAgent();

        if (preg_match('/Windows/', $userAgent)) {
            return 'Windows';
        } elseif (preg_match('/Mac/', $userAgent)) {
            return 'macOS';
        } elseif (preg_match('/Linux/', $userAgent)) {
            return 'Linux';
        } elseif (preg_match('/Android/', $userAgent)) {
            return 'Android';
        } elseif (preg_match('/iOS|iPhone|iPad/', $userAgent)) {
            return 'iOS';
        }

        return 'Unknown';
    }

    /**
     * Store affiliate data in session for booking flow.
     */
    private function storeAffiliateDataInSession(AffiliateQrCode $qrCode, \App\Models\Affiliate\AffiliateCustomerScan $customerScan): void
    {
        $businessModel = $qrCode->business->getEffectiveBusinessModel();

        session([
            'affiliate_data' => [
                'business_id' => $qrCode->business_id,
                'qr_code_id' => $qrCode->id,
                'customer_scan_id' => $customerScan->id,
                'scan_token' => $customerScan->scan_token,
                'discount_type' => $businessModel['discount_type'],
                'discount_value' => $businessModel['discount_value'],
                'discount_rate' => $businessModel['discount_value'], // For backward compatibility
                'business_name' => $qrCode->business->name,
                'scanned_at' => now()->toISOString(),
            ]
        ]);
    }

    /**
     * Get affiliate data from session.
     */
    public function getAffiliateSessionData(): ?array
    {
        return session('affiliate_data');
    }

    /**
     * Clear affiliate data from session.
     */
    public function clearAffiliateSessionData(): void
    {
        session()->forget('affiliate_data');
    }

    /**
     * Validate QR code status.
     */
    public function validateQrCodeUsage(AffiliateQrCode $qrCode): bool
    {
        // Check if QR code is valid (includes status checks)
        return $qrCode->isValid();
    }

    /**
     * Apply affiliate discount to booking.
     */
    public function applyAffiliateDiscount(float $originalPrice, float $discountRate, string $discountType = 'percentage'): array
    {
        if ($discountType === 'fixed_amount') {
            $discountAmount = min($discountRate, $originalPrice);
        } else {
            $discountAmount = $originalPrice * ($discountRate / 100);
        }

        $finalPrice = $originalPrice - $discountAmount;

        return [
            'original_price' => $originalPrice,
            'discount_type' => $discountType,
            'discount_rate' => $discountType === 'percentage' ? $discountRate : null,
            'discount_amount' => $discountAmount,
            'final_price' => $finalPrice,
        ];
    }
}