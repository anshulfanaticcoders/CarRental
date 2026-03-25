<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\User;
use App\Services\VrooemGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingCancellationGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_gateway_cancellation_is_used_when_gateway_booking_metadata_exists_even_when_the_legacy_flag_is_disabled(): void
    {
        config(['vrooem.enabled' => false]);

        $user = User::factory()->create();
        $booking = $this->createBookingForUser($user, [
            'provider_source' => 'recordgo',
            'provider_booking_ref' => 'SUP-BOOK-1',
            'provider_metadata' => [
                'gateway_booking_id' => 'gw_1',
                'gateway_supplier_id' => 'record_go',
            ],
        ]);

        $this->mock(VrooemGatewayService::class, function ($mock): void {
            $mock->shouldReceive('cancelBooking')
                ->once()
                ->with('gw_1', 'record_go', 'SUP-BOOK-1', 'Need to cancel')
                ->andReturn(['status' => 'cancelled']);
        });

        $response = $this
            ->actingAs($user)
            ->from(route('profile.bookings.all', ['locale' => 'en']))
            ->post(route('booking.cancel', ['locale' => 'en']), [
                'booking_id' => $booking->id,
                'cancellation_reason' => 'Need to cancel',
            ]);

        $response->assertRedirect();

        $booking->refresh();
        $this->assertSame('cancelled', $booking->booking_status);
        $this->assertSame('Need to cancel', $booking->cancellation_reason);
        $this->assertStringContainsString('Gateway Cancel', (string) $booking->notes);
    }

    public function test_external_cancellation_is_rejected_when_gateway_metadata_is_missing(): void
    {
        config(['vrooem.enabled' => false]);

        $user = User::factory()->create();
        $booking = $this->createBookingForUser($user, [
            'provider_source' => 'greenmotion',
            'provider_booking_ref' => 'GM-123',
            'provider_metadata' => [],
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson(route('booking.cancel', ['locale' => 'en']), [
                'booking_id' => $booking->id,
                'cancellation_reason' => 'Need to cancel',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Provider gateway cancellation metadata is missing.');

        $booking->refresh();
        $this->assertSame('confirmed', $booking->booking_status);
    }

    private function createBookingForUser(User $user, array $overrides = []): Booking
    {
        $customer = Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => $user->email,
            'phone' => $user->phone,
            'driver_age' => 30,
        ]);

        return Booking::create(array_merge([
            'booking_number' => 'BKTEST-' . uniqid(),
            'customer_id' => $customer->id,
            'vehicle_id' => null,
            'provider_source' => 'recordgo',
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
        ], $overrides));
    }
}
