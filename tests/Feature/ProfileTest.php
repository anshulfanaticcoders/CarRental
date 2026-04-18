<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('profile.edit', ['locale' => 'en']));

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('profile.update', ['locale' => 'en']), [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
                'phone' => $user->phone,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit', ['locale' => 'en']));

        $user->refresh();

        $this->assertSame('Test', $user->first_name);
        $this->assertSame('User', $user->last_name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('profile.update', ['locale' => 'en']), [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit', ['locale' => 'en']));

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete(route('profile.destroy', ['locale' => 'en']), [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('profile.edit', ['locale' => 'en']))
            ->delete(route('profile.destroy', ['locale' => 'en']), [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect(route('profile.edit', ['locale' => 'en']));

        $this->assertNotNull($user->fresh());
    }

    public function test_profile_bookings_page_recovers_bookings_from_a_matching_customer_email_and_backfills_the_user_link(): void
    {
        $user = User::factory()->create([
            'email' => 'bookings@example.com',
        ]);

        $oldUser = User::factory()->create([
            'email' => 'legacy-bookings@example.com',
        ]);

        $customer = Customer::create([
            'user_id' => $oldUser->id,
            'first_name' => 'Booking',
            'last_name' => 'Customer',
            'email' => 'bookings@example.com',
            'phone' => $user->phone,
            'driver_age' => 30,
        ]);

        $booking = Booking::create([
            'booking_number' => 'BKTEST-' . uniqid(),
            'customer_id' => $customer->id,
            'vehicle_id' => null,
            'provider_source' => 'internal',
            'provider_vehicle_id' => 'vehicle-1',
            'provider_booking_ref' => 'SUP-REF-1',
            'provider_metadata' => [],
            'vehicle_name' => 'Provider Vehicle',
            'pickup_date' => now()->addDay(),
            'return_date' => now()->addDays(2),
            'pickup_time' => '09:00',
            'return_time' => '09:00',
            'pickup_location' => 'Airport',
            'return_location' => 'Airport',
            'plan' => 'BAS',
            'total_days' => 1,
            'base_price' => 100,
            'extra_charges' => 0,
            'tax_amount' => 0,
            'total_amount' => 100,
            'pending_amount' => 100,
            'amount_paid' => 0,
            'booking_currency' => 'EUR',
            'payment_status' => 'partial',
            'booking_status' => 'confirmed',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('profile.bookings.all', ['locale' => 'en']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Profile/Bookings/AllBookings')
            ->where('bookings.data.0.booking_number', $booking->booking_number)
        );

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'user_id' => $user->id,
        ]);
    }
}
