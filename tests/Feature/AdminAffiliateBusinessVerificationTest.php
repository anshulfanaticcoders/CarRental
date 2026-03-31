<?php

namespace Tests\Feature;

use App\Models\Affiliate\AffiliateBusiness;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminAffiliateBusinessVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_business_redirects_back_for_inertia_requests(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $business = AffiliateBusiness::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Partner One',
            'contact_email' => 'partner@example.com',
            'contact_phone' => '+1234567890',
            'verification_status' => 'pending',
            'status' => 'pending',
            'business_type' => 'hotel',
            'legal_address' => '123 Main Street',
            'city' => 'Antwerp',
            'country' => 'Belgium',
            'postal_code' => '2000',
            'currency' => 'EUR',
            'dashboard_access_token' => 'AFF-TEST-TOKEN-123',
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.affiliate.partners'))
            ->withHeader('X-Inertia', 'true')
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->post(route('admin.affiliate.businesses.verify', ['businessId' => $business->id]));

        $response->assertRedirect(route('admin.affiliate.partners'));
        $response->assertSessionHas('success', 'Business verified successfully');

        $business->refresh();
        $this->assertSame('verified', $business->verification_status);
        $this->assertSame('active', $business->status);
        $this->assertNotNull($business->verified_at);
    }
}
