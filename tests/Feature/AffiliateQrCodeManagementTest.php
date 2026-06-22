<?php

namespace Tests\Feature;

use App\Models\Affiliate\AffiliateBusiness;
use App\Models\Affiliate\AffiliateBusinessLocation;
use App\Models\Affiliate\AffiliateQrCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AffiliateQrCodeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_affiliate_can_delete_own_qr_code_without_server_error(): void
    {
        Storage::fake('upcloud');

        [$user, $business, $qrCode] = $this->createAffiliateQrCodeFixture();

        Storage::disk('upcloud')->put($qrCode->qr_image_path, 'qr-image');

        $response = $this
            ->actingAs($user)
            ->from(route('affiliate.qr-codes', ['locale' => 'en']))
            ->delete(route('affiliate.qr-codes.destroy', [
                'locale' => 'en',
                'qrCode' => $qrCode->id,
            ]));

        $response->assertRedirect(route('affiliate.qr-codes', ['locale' => 'en']));
        $response->assertSessionHas('success', 'QR code deleted successfully.');

        $this->assertSoftDeleted('affiliate_qr_codes', ['id' => $qrCode->id]);
        $this->assertSame($business->id, $qrCode->business_id);
    }

    public function test_affiliate_cannot_delete_another_business_qr_code(): void
    {
        [$user] = $this->createAffiliateQrCodeFixture('owner@example.test', '+971501111111', 'OWNERQR');
        [, , $otherQrCode] = $this->createAffiliateQrCodeFixture('other@example.test', '+971502222222', 'OTHERQR');

        $response = $this
            ->actingAs($user)
            ->from(route('affiliate.qr-codes', ['locale' => 'en']))
            ->delete(route('affiliate.qr-codes.destroy', [
                'locale' => 'en',
                'qrCode' => $otherQrCode->id,
            ]));

        $response->assertRedirect(route('affiliate.qr-codes', ['locale' => 'en']));
        $response->assertSessionHasErrors(['qr_code']);

        $this->assertDatabaseHas('affiliate_qr_codes', [
            'id' => $otherQrCode->id,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_can_delete_qr_code_from_partner_detail(): void
    {
        Storage::fake('upcloud');

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        [, $business, $qrCode] = $this->createAffiliateQrCodeFixture();

        Storage::disk('upcloud')->put($qrCode->qr_image_path, 'qr-image');

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.affiliate.partners.show', ['id' => $business->id]))
            ->delete(route('admin.affiliate.partners.qr-codes.destroy', [
                'id' => $business->id,
                'qrCodeId' => $qrCode->id,
            ]));

        $response->assertRedirect(route('admin.affiliate.partners.show', ['id' => $business->id]));
        $response->assertSessionHas('success', 'QR code deleted successfully.');

        $this->assertSoftDeleted('affiliate_qr_codes', ['id' => $qrCode->id]);
    }

    public function test_affiliate_qr_create_requires_visible_location_data(): void
    {
        [$user] = $this->createAffiliateQrCodeFixture();

        $response = $this
            ->actingAs($user)
            ->from(route('affiliate.qr-codes', ['locale' => 'en']))
            ->post(route('affiliate.qr-codes.store', ['locale' => 'en']), [
                'label' => 'Missing Location',
            ]);

        $response->assertRedirect(route('affiliate.qr-codes', ['locale' => 'en']));
        $response->assertSessionHasErrors(['location_id']);
    }

    public function test_affiliate_can_create_qr_code_with_manual_location_data(): void
    {
        Storage::fake('upcloud');

        [$user, $business] = $this->createAffiliateQrCodeFixture();

        $response = $this
            ->actingAs($user)
            ->from(route('affiliate.qr-codes', ['locale' => 'en']))
            ->post(route('affiliate.qr-codes.store', ['locale' => 'en']), [
                'label' => 'Manual Location QR',
                'location_id' => 'new',
                'location_name' => 'Manual Lobby',
                'address_line_1' => '1 Browser Test Street',
                'city' => 'Antwerp',
                'country' => 'Belgium',
                'postal_code' => '2000',
                'latitude' => 51.22134,
                'longitude' => 4.40515,
            ]);

        $response->assertRedirect(route('affiliate.qr-codes', ['locale' => 'en']));
        $response->assertSessionHas('success', 'QR code created successfully!');

        $this->assertDatabaseHas('affiliate_business_locations', [
            'business_id' => $business->id,
            'name' => 'Manual Lobby',
            'city' => 'Antwerp',
            'country' => 'Belgium',
        ]);
        $this->assertDatabaseCount('affiliate_qr_codes', 2);
    }

    public function test_influencer_can_create_locationless_share_qr_code(): void
    {
        Storage::fake('upcloud');

        [$user, $business] = $this->createInfluencerBusinessFixture();

        $response = $this
            ->actingAs($user)
            ->from(route('affiliate.qr-codes', ['locale' => 'en']))
            ->post(route('affiliate.qr-codes.store', ['locale' => 'en']), [
                'label' => 'Influencer Share Link',
            ]);

        $response->assertRedirect(route('affiliate.qr-codes', ['locale' => 'en']));
        $response->assertSessionHas('success', 'Share link created successfully!');

        $this->assertDatabaseHas('affiliate_qr_codes', [
            'business_id' => $business->id,
            'location_id' => null,
            'status' => 'active',
        ]);
        $this->assertDatabaseMissing('affiliate_business_locations', [
            'business_id' => $business->id,
        ]);
    }

    public function test_influencer_share_qr_code_creation_is_idempotent(): void
    {
        Storage::fake('upcloud');

        [$user, $business] = $this->createInfluencerBusinessFixture();

        foreach (range(1, 2) as $attempt) {
            $response = $this
                ->actingAs($user)
                ->from(route('affiliate.qr-codes', ['locale' => 'en']))
                ->post(route('affiliate.qr-codes.store', ['locale' => 'en']), [
                    'label' => 'Influencer Share Link',
                ]);

            $response->assertRedirect(route('affiliate.qr-codes', ['locale' => 'en']));
        }

        $this->assertSame(1, AffiliateQrCode::where('business_id', $business->id)
            ->whereNull('location_id')
            ->where('status', 'active')
            ->count());
    }

    private function createAffiliateQrCodeFixture(
        string $email = 'affiliate@example.test',
        string $phone = '+971501234567',
        string $shortCode = 'AFFQR001'
    ): array {
        $user = User::factory()->create([
            'email' => $email,
            'phone' => $phone,
            'role' => 'affiliate',
            'status' => 'active',
        ]);

        $business = AffiliateBusiness::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'name' => 'Affiliate Partner',
            'business_type' => 'hotel',
            'contact_email' => $email,
            'contact_phone' => $phone,
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'currency' => 'EUR',
            'verification_status' => 'verified',
            'status' => 'active',
        ]);

        $location = AffiliateBusinessLocation::create([
            'uuid' => (string) Str::uuid(),
            'business_id' => $business->id,
            'location_code' => 'LOC-'.$shortCode,
            'name' => 'Partner Lobby',
            'address_line_1' => '1 Partner Street',
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'postal_code' => '00000',
            'latitude' => 25.2048,
            'longitude' => 55.2708,
        ]);

        $qrCode = AffiliateQrCode::create([
            'uuid' => (string) Str::uuid(),
            'business_id' => $business->id,
            'location_id' => $location->id,
            'qr_code_value' => 'AFFILIATE-'.$shortCode,
            'qr_hash' => hash('sha256', 'AFFILIATE-'.$shortCode),
            'short_code' => $shortCode,
            'qr_url' => url('/en/affiliate/qr/'.$shortCode),
            'qr_image_path' => 'affiliate/qr/'.$shortCode.'.png',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'status' => 'active',
        ]);

        return [$user, $business, $qrCode];
    }

    private function createInfluencerBusinessFixture(): array
    {
        $user = User::factory()->create([
            'email' => 'influencer@example.test',
            'phone' => '+971509999001',
            'role' => 'affiliate',
            'status' => 'active',
        ]);

        $business = AffiliateBusiness::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'name' => 'Influencer Partner',
            'business_type' => 'influencer',
            'contact_email' => $user->email,
            'contact_phone' => $user->phone,
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'currency' => 'EUR',
            'verification_status' => 'verified',
            'status' => 'active',
        ]);

        return [$user, $business];
    }
}
