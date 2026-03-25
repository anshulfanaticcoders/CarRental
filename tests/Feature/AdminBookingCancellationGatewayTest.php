<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\User;
use App\Services\VrooemGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminBookingCancellationGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cancellation_uses_gateway_when_gateway_metadata_exists(): void
    {
        Notification::fake();
        config(['vrooem.enabled' => false]);

        $admin = User::factory()->create(['role' => 'admin']);
        $booking = $this->createBooking([
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
            ->actingAs($admin)
            ->from(route('customer-bookings.index'))
            ->post(route('customer-bookings.cancel', ['id' => $booking->id]), [
                'cancellation_reason' => 'Need to cancel',
            ]);

        $response->assertRedirect();

        $booking->refresh();
        $this->assertSame('cancelled', $booking->booking_status);
        $this->assertSame('Need to cancel', $booking->cancellation_reason);
    }

    public function test_admin_cancellation_rejects_external_booking_without_gateway_metadata(): void
    {
        Notification::fake();
        config(['vrooem.enabled' => false]);

        $admin = User::factory()->create(['role' => 'admin']);
        $booking = $this->createBooking([
            'provider_source' => 'greenmotion',
            'provider_booking_ref' => 'GM-123',
            'provider_metadata' => [],
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('customer-bookings.index'))
            ->post(route('customer-bookings.cancel', ['id' => $booking->id]), [
                'cancellation_reason' => 'Need to cancel',
            ]);

        $response->assertRedirect(route('customer-bookings.index'));
        $response->assertSessionHas('error', 'Provider gateway cancellation metadata is missing.');

        $booking->refresh();
        $this->assertSame('confirmed', $booking->booking_status);
    }

    private function createBooking(array $overrides = []): Booking
    {
        $customerUser = User::factory()->create();
        $customer = Customer::create([
            'user_id' => $customerUser->id,
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => $customerUser->email,
            'phone' => $customerUser->phone,
            'driver_age' => 30,
        ]);

        return Booking::create(array_merge([
            'booking_number' => 'BKADMIN-' . uniqid(),
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
