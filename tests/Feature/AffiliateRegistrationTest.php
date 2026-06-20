<?php

namespace Tests\Feature;

use App\Models\Affiliate\AffiliateBusiness;
use App\Models\User;
use App\Notifications\Affiliate\BusinessRegistrationAdminNotification;
use App\Notifications\Affiliate\BusinessRegistrationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class AffiliateRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_create_affiliate_account_with_frontend_business_type_values(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'email' => 'admin@example.test',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $payload = [
            'first_name' => 'Nassim',
            'last_name' => 'Partner',
            'email' => 'affiliate.partner@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'business_name' => 'Vrooem Smoke Partner',
            'business_type' => 'travel_agency',
            'contact_phone' => '+971501234567',
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'bank_name' => 'Smoke Bank',
            'bank_iban' => 'AE070331234567890123456',
            'bank_bic' => 'SMOKEAEXXX',
            'bank_account_name' => 'Vrooem Smoke Partner LLC',
            'currency' => 'EUR',
        ];

        $response = $this->post(route('affiliate.register.store', ['locale' => 'en']), $payload);

        $response->assertRedirect(route('affiliate.dashboard', ['locale' => 'en']));
        $response->assertSessionHas('success', 'Welcome! Your affiliate account has been created.');

        $user = User::where('email', $payload['email'])->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('affiliate', $user->role);

        $this->assertDatabaseHas('users', [
            'email' => $payload['email'],
            'first_name' => $payload['first_name'],
            'last_name' => $payload['last_name'],
            'role' => 'affiliate',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('affiliate_businesses', [
            'user_id' => $user->id,
            'name' => $payload['business_name'],
            'business_type' => 'travel_agency',
            'contact_email' => $payload['email'],
            'contact_phone' => $payload['contact_phone'],
            'city' => $payload['city'],
            'country' => $payload['country'],
            'currency' => $payload['currency'],
            'verification_status' => 'pending',
            'status' => 'active',
        ]);

        $business = AffiliateBusiness::where('contact_email', $payload['email'])->firstOrFail();

        Notification::assertSentTo($user, BusinessRegistrationNotification::class);
        Notification::assertSentTo($admin, BusinessRegistrationAdminNotification::class, function ($notification) use ($business) {
            return $notification->toArray($business)['business_id'] === $business->id;
        });
    }

    public function test_validate_email_accepts_new_email(): void
    {
        $response = $this->postJson(route('validate-email', ['locale' => 'en']), [
            'email' => 'new-affiliate@example.test',
        ]);

        $response
            ->assertOk()
            ->assertJson(['message' => 'Email is valid']);
    }

    public function test_guest_can_create_affiliate_account_with_registry_currency(): void
    {
        Notification::fake();

        $payload = [
            'first_name' => 'Anshul',
            'last_name' => 'Partner',
            'email' => 'affiliate.inr@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'business_name' => 'Hotel Hayat',
            'business_type' => 'hotel',
            'contact_phone' => '+91888776552',
            'city' => 'Dharamsala',
            'country' => 'India',
            'currency' => 'INR',
        ];

        $response = $this->post(route('affiliate.register.store', ['locale' => 'en']), $payload);

        $response->assertRedirect(route('affiliate.dashboard', ['locale' => 'en']));

        $this->assertDatabaseHas('affiliate_businesses', [
            'contact_email' => $payload['email'],
            'contact_phone' => $payload['contact_phone'],
            'country' => $payload['country'],
            'currency' => 'INR',
        ]);
    }

    public function test_validate_email_rejects_existing_email(): void
    {
        User::factory()->create([
            'email' => 'taken-affiliate@example.test',
        ]);

        $response = $this->postJson(route('validate-email', ['locale' => 'en']), [
            'email' => 'taken-affiliate@example.test',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_validate_email_rejects_existing_affiliate_business_email(): void
    {
        AffiliateBusiness::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Existing Partner',
            'business_type' => 'hotel',
            'contact_email' => 'existing-business@example.test',
            'contact_phone' => '+971501111111',
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'currency' => 'EUR',
            'verification_status' => 'pending',
            'status' => 'active',
        ]);

        $response = $this->postJson(route('validate-email', ['locale' => 'en']), [
            'email' => 'existing-business@example.test',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_validate_email_accepts_soft_deleted_affiliate_business_email(): void
    {
        $business = AffiliateBusiness::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Deleted Partner',
            'business_type' => 'hotel',
            'contact_email' => 'deleted-business@example.test',
            'contact_phone' => '+971501777777',
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'currency' => 'EUR',
            'verification_status' => 'pending',
            'status' => 'active',
        ]);
        $business->delete();

        $response = $this->postJson(route('validate-email', ['locale' => 'en']), [
            'email' => 'deleted-business@example.test',
        ]);

        $response
            ->assertOk()
            ->assertJson(['message' => 'Email is valid']);
    }

    public function test_validate_contact_rejects_existing_phone(): void
    {
        User::factory()->create([
            'phone' => '+971501234567',
        ]);

        $response = $this->postJson(route('validate-contact', ['locale' => 'en']), [
            'email' => 'new-affiliate@example.test',
            'phone' => '+971501234567',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_validate_contact_rejects_existing_affiliate_business_phone(): void
    {
        AffiliateBusiness::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Existing Phone Partner',
            'business_type' => 'hotel',
            'contact_email' => 'existing-phone-business@example.test',
            'contact_phone' => '+971501222222',
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'currency' => 'EUR',
            'verification_status' => 'pending',
            'status' => 'active',
        ]);

        $response = $this->postJson(route('validate-contact', ['locale' => 'en']), [
            'email' => 'new-affiliate@example.test',
            'phone' => '+971501222222',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_validate_contact_accepts_soft_deleted_affiliate_business_phone(): void
    {
        $business = AffiliateBusiness::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Deleted Phone Partner',
            'business_type' => 'hotel',
            'contact_email' => 'deleted-phone-business@example.test',
            'contact_phone' => '+971501888888',
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'currency' => 'EUR',
            'verification_status' => 'pending',
            'status' => 'active',
        ]);
        $business->delete();

        $response = $this->postJson(route('validate-contact', ['locale' => 'en']), [
            'email' => 'new-affiliate@example.test',
            'phone' => '+971501888888',
        ]);

        $response
            ->assertOk()
            ->assertJson(['message' => 'Contact information is valid']);
    }

    public function test_validate_contact_accepts_new_email_and_phone(): void
    {
        $response = $this->postJson(route('validate-contact', ['locale' => 'en']), [
            'email' => 'new-affiliate@example.test',
            'phone' => '+971509999999',
        ]);

        $response
            ->assertOk()
            ->assertJson(['message' => 'Contact information is valid']);
    }

    public function test_affiliate_registration_returns_validation_errors_for_missing_required_fields(): void
    {
        Notification::fake();

        $response = $this
            ->from(route('affiliate.register', ['locale' => 'en']))
            ->post(route('affiliate.register.store', ['locale' => 'en']), []);

        $response
            ->assertRedirect(route('affiliate.register', ['locale' => 'en']))
            ->assertSessionHasErrors([
                'first_name',
                'last_name',
                'email',
                'password',
                'business_name',
                'business_type',
                'contact_phone',
                'city',
                'country',
            ]);

        $this->assertDatabaseCount('affiliate_businesses', 0);
        Notification::assertNothingSent();
    }

    public function test_affiliate_registration_rejects_duplicate_email_without_server_error(): void
    {
        Notification::fake();

        User::factory()->create([
            'email' => 'duplicate-affiliate@example.test',
        ]);

        $payload = [
            'first_name' => 'Duplicate',
            'last_name' => 'Email',
            'email' => 'duplicate-affiliate@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'business_name' => 'Duplicate Email Partner',
            'business_type' => 'travel_agency',
            'contact_phone' => '+971501333333',
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'currency' => 'EUR',
        ];

        $response = $this
            ->from(route('affiliate.register', ['locale' => 'en']))
            ->post(route('affiliate.register.store', ['locale' => 'en']), $payload);

        $response
            ->assertRedirect(route('affiliate.register', ['locale' => 'en']))
            ->assertSessionHasErrors(['email']);

        $this->assertDatabaseMissing('affiliate_businesses', [
            'contact_email' => $payload['email'],
        ]);
        Notification::assertNothingSent();
    }

    public function test_affiliate_registration_can_reuse_soft_deleted_affiliate_contact_details(): void
    {
        Notification::fake();

        $deletedBusiness = AffiliateBusiness::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Deleted Affiliate',
            'business_type' => 'hotel',
            'contact_email' => 'reuse-deleted-affiliate@example.test',
            'contact_phone' => '+971501999999',
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'currency' => 'EUR',
            'verification_status' => 'pending',
            'status' => 'active',
        ]);
        $deletedBusiness->delete();

        $payload = [
            'first_name' => 'Reuse',
            'last_name' => 'Deleted',
            'email' => 'reuse-deleted-affiliate@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'business_name' => 'Reuse Deleted Partner',
            'business_type' => 'travel_agency',
            'contact_phone' => '+971501999999',
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'currency' => 'EUR',
        ];

        $response = $this->post(route('affiliate.register.store', ['locale' => 'en']), $payload);

        $response->assertRedirect(route('affiliate.dashboard', ['locale' => 'en']));

        $this->assertDatabaseHas('users', [
            'email' => $payload['email'],
            'role' => 'affiliate',
        ]);
        $this->assertDatabaseHas('affiliate_businesses', [
            'contact_email' => $payload['email'],
            'contact_phone' => $payload['contact_phone'],
            'deleted_at' => null,
        ]);
        $this->assertSame(2, AffiliateBusiness::withTrashed()
            ->where('contact_email', $payload['email'])
            ->count());
    }

    public function test_affiliate_registration_rejects_duplicate_contact_phone_without_server_error(): void
    {
        Notification::fake();

        User::factory()->create([
            'phone' => '+971501234567',
        ]);

        $payload = [
            'first_name' => 'Duplicate',
            'last_name' => 'Phone',
            'email' => 'duplicate-phone-affiliate@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'business_name' => 'Duplicate Phone Partner',
            'business_type' => 'travel_agency',
            'contact_phone' => '+971501234567',
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'currency' => 'EUR',
        ];

        $response = $this
            ->from(route('affiliate.register', ['locale' => 'en']))
            ->post(route('affiliate.register.store', ['locale' => 'en']), $payload);

        $response
            ->assertRedirect(route('affiliate.register', ['locale' => 'en']))
            ->assertSessionHasErrors(['contact_phone']);

        $this->assertDatabaseMissing('affiliate_businesses', [
            'contact_email' => $payload['email'],
        ]);
        Notification::assertNothingSent();
    }

    public function test_affiliate_registration_rejects_existing_affiliate_contact_phone_without_server_error(): void
    {
        Notification::fake();

        AffiliateBusiness::create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Existing Affiliate Phone',
            'business_type' => 'hotel',
            'contact_email' => 'existing-affiliate-phone@example.test',
            'contact_phone' => '+971501444444',
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'currency' => 'EUR',
            'verification_status' => 'pending',
            'status' => 'active',
        ]);

        $payload = [
            'first_name' => 'Duplicate',
            'last_name' => 'AffiliatePhone',
            'email' => 'new-affiliate-phone@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'business_name' => 'Duplicate Affiliate Phone Partner',
            'business_type' => 'travel_agency',
            'contact_phone' => '+971501444444',
            'city' => 'Dubai',
            'country' => 'United Arab Emirates',
            'currency' => 'EUR',
        ];

        $response = $this
            ->from(route('affiliate.register', ['locale' => 'en']))
            ->post(route('affiliate.register.store', ['locale' => 'en']), $payload);

        $response
            ->assertRedirect(route('affiliate.register', ['locale' => 'en']))
            ->assertSessionHasErrors(['contact_phone']);

        $this->assertDatabaseMissing('affiliate_businesses', [
            'contact_email' => $payload['email'],
        ]);
        Notification::assertNothingSent();
    }
}
