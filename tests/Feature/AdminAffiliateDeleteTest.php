<?php

namespace Tests\Feature;

use App\Models\Affiliate\AffiliateBusiness;
use App\Models\Affiliate\AffiliateBusinessLocation;
use App\Models\Affiliate\AffiliateBusinessModel;
use App\Models\Affiliate\AffiliatePayout;
use App\Models\Affiliate\AffiliateQrCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminAffiliateDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_soft_delete_an_affiliate_without_deleting_payout_history(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $business = AffiliateBusiness::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Partner One',
            'contact_email' => 'partner@example.com',
            'contact_phone' => '+1234567890',
            'verification_status' => 'verified',
            'status' => 'active',
            'business_type' => 'hotel',
            'legal_address' => '123 Main Street',
            'city' => 'Antwerp',
            'country' => 'Belgium',
            'postal_code' => '2000',
            'currency' => 'EUR',
            'dashboard_access_token' => 'AFF-TEST-TOKEN-123',
        ]);

        $location = AffiliateBusinessLocation::create([
            'uuid' => (string) Str::uuid(),
            'business_id' => $business->id,
            'location_code' => 'ANT-001',
            'name' => 'Antwerp Downtown',
            'address_line_1' => '1 Main Street',
            'city' => 'Antwerp',
            'country' => 'Belgium',
            'postal_code' => '2000',
            'latitude' => 51.219448,
            'longitude' => 4.402464,
        ]);

        $qrCode = AffiliateQrCode::create([
            'uuid' => (string) Str::uuid(),
            'business_id' => $business->id,
            'location_id' => $location->id,
            'qr_code_value' => 'AFFILIATE-QR-001',
            'qr_hash' => hash('sha256', 'AFFILIATE-QR-001'),
            'short_code' => 'AFF001',
            'qr_url' => 'https://vrooem.test/qr/AFF001',
            'qr_image_path' => 'affiliate/qr/one.png',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'status' => 'active',
        ]);

        $businessModel = AffiliateBusinessModel::create([
            'uuid' => (string) Str::uuid(),
            'business_id' => $business->id,
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'commission_rate' => 12,
            'commission_type' => 'percentage',
            'is_active' => true,
        ]);

        $payout = AffiliatePayout::create([
            'uuid' => (string) Str::uuid(),
            'business_id' => $business->id,
            'total_amount' => 125.50,
            'currency' => 'EUR',
            'status' => 'pending',
            'period_start' => now()->subMonth()->startOfMonth()->toDateString(),
            'period_end' => now()->subMonth()->endOfMonth()->toDateString(),
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.affiliate.partners'))
            ->withHeader('X-Inertia', 'true')
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->delete(route('admin.affiliate.partners.destroy', ['id' => $business->id]));

        $response->assertRedirect(route('admin.affiliate.partners'));
        $response->assertSessionHas('success', 'Affiliate deleted successfully.');

        $this->assertSoftDeleted('affiliate_businesses', ['id' => $business->id]);
        $this->assertSoftDeleted('affiliate_business_locations', ['id' => $location->id]);
        $this->assertSoftDeleted('affiliate_qr_codes', ['id' => $qrCode->id]);
        $this->assertDatabaseMissing('affiliate_business_models', ['id' => $businessModel->id]);
        $this->assertDatabaseHas('affiliate_payouts', ['id' => $payout->id, 'business_id' => $business->id]);
    }
}
