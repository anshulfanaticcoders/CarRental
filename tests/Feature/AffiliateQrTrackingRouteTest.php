<?php

namespace Tests\Feature;

use App\Models\Affiliate\AffiliateBusiness;
use App\Models\Affiliate\AffiliateBusinessLocation;
use App\Models\Affiliate\AffiliateQrCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AffiliateQrTrackingRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_localized_track_route_creates_customer_scan_from_encoded_payload(): void
    {
        $qrCode = $this->createAffiliateQrCode('TRACKSMOKE');
        $trackingData = $this->encodedTrackingData($qrCode);

        $response = $this->get(route('affiliate.qr.track', [
            'locale' => 'en',
            'trackingData' => $trackingData,
        ]));

        $response->assertRedirect(route('welcome', ['locale' => 'en']));

        $this->assertDatabaseHas('affiliate_customer_scans', [
            'qr_code_id' => $qrCode->id,
            'scan_result' => 'success',
        ]);
    }

    public function test_localized_short_qr_route_creates_customer_scan(): void
    {
        $qrCode = $this->createAffiliateQrCode('SHORTSMOKE');

        $response = $this->get(route('affiliate.qr.landing', [
            'locale' => 'en',
            'shortCode' => $qrCode->short_code,
        ]));

        $response->assertRedirect('/en/vehicles');

        $this->assertDatabaseHas('affiliate_customer_scans', [
            'qr_code_id' => $qrCode->id,
            'scan_result' => 'success',
        ]);
    }

    public function test_localized_short_influencer_qr_route_creates_locationless_customer_scan(): void
    {
        $qrCode = $this->createAffiliateQrCode('INFLUSHORT', 'influencer', false);

        $response = $this->get(route('affiliate.qr.landing', [
            'locale' => 'en',
            'shortCode' => $qrCode->short_code,
        ]));

        $response->assertRedirect(route('vehicles.index', ['locale' => 'en']));
        $response->assertSessionHas('affiliate_data', function (array $affiliateData) use ($qrCode) {
            return $affiliateData['qr_code_id'] === $qrCode->id
                && $affiliateData['business_id'] === $qrCode->business_id;
        });

        $this->assertDatabaseHas('affiliate_customer_scans', [
            'qr_code_id' => $qrCode->id,
            'scan_result' => 'success',
        ]);
    }

    public function test_localized_track_route_creates_customer_scan_for_locationless_influencer_payload(): void
    {
        $qrCode = $this->createAffiliateQrCode('INFLUTRACK', 'influencer', false);
        $trackingData = $this->encodedTrackingData($qrCode);

        $response = $this->get(route('affiliate.qr.track', [
            'locale' => 'en',
            'trackingData' => $trackingData,
        ]));

        $response->assertRedirect(route('welcome', ['locale' => 'en']));

        $this->assertDatabaseHas('affiliate_customer_scans', [
            'qr_code_id' => $qrCode->id,
            'scan_result' => 'success',
        ]);
    }

    public function test_legacy_root_track_route_redirects_to_localized_route(): void
    {
        $qrCode = $this->createAffiliateQrCode('ROOTTRACK');
        $trackingData = $this->encodedTrackingData($qrCode);

        $response = $this->get(route('affiliate.qr.track.root', [
            'trackingData' => $trackingData,
        ]));

        $response->assertRedirect(route('affiliate.qr.track', [
            'locale' => 'en',
            'trackingData' => $trackingData,
        ]));
    }

    public function test_legacy_root_short_qr_route_redirects_to_localized_route(): void
    {
        $qrCode = $this->createAffiliateQrCode('ROOTSHORT');

        $response = $this->get(route('affiliate.qr.landing.root', [
            'shortCode' => $qrCode->short_code,
        ]));

        $response->assertRedirect(route('affiliate.qr.landing', [
            'locale' => 'en',
            'shortCode' => $qrCode->short_code,
        ]));
    }

    private function createAffiliateQrCode(
        string $shortCode,
        string $businessType = 'travel_agency',
        bool $withLocation = true
    ): AffiliateQrCode {
        $owner = User::factory()->create([
            'role' => 'affiliate',
            'status' => 'active',
        ]);

        $business = AffiliateBusiness::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $owner->id,
            'name' => 'QR Route Smoke',
            'business_type' => $businessType,
            'contact_email' => $owner->email,
            'contact_phone' => '+1000000'.random_int(1000, 9999),
            'legal_address' => 'Airport Road',
            'city' => 'Dubai',
            'country' => 'AE',
            'postal_code' => '00000',
            'currency' => 'EUR',
            'verification_status' => 'verified',
            'status' => 'active',
            'dashboard_access_token' => 'AFF-'.Str::upper(Str::random(16)),
            'verified_at' => now(),
        ]);

        $location = null;

        if ($withLocation) {
            $location = AffiliateBusinessLocation::create([
                'uuid' => (string) Str::uuid(),
                'business_id' => $business->id,
                'location_code' => 'QR-SMOKE-'.$shortCode,
                'name' => 'QR Route Desk',
                'address_line_1' => 'Airport Road',
                'city' => 'Dubai',
                'country' => 'AE',
                'postal_code' => '00000',
                'latitude' => 25.251369,
                'longitude' => 55.347204,
                'verification_status' => 'verified',
                'is_active' => true,
                'verified_at' => now(),
            ]);
        }

        return AffiliateQrCode::create([
            'uuid' => (string) Str::uuid(),
            'business_id' => $business->id,
            'location_id' => $location?->id,
            'qr_code_value' => 'affiliate-'.$shortCode,
            'qr_hash' => hash('sha256', 'affiliate-'.$shortCode),
            'short_code' => $shortCode,
            'qr_url' => url('/en/affiliate/qr/'.$shortCode),
            'qr_image_path' => 'affiliate/'.$shortCode.'.png',
            'discount_type' => 'percentage',
            'discount_value' => 0,
            'status' => 'active',
        ]);
    }

    private function encodedTrackingData(AffiliateQrCode $qrCode): string
    {
        $payload = [
            'type' => 'affiliate_qr',
            'business_id' => $qrCode->business_id,
            'location_id' => $qrCode->location_id,
            'qr_id' => $qrCode->short_code,
            'timestamp' => now()->timestamp,
            'version' => '1.0',
        ];

        return rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
    }
}
