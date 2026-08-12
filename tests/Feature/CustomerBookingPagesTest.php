<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CustomerBookingPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'customer']);
        $this->customer = Customer::create([
            'user_id' => $this->user->id,
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => $this->user->email,
            'phone' => $this->user->phone,
            'driver_age' => 30,
        ]);
    }

    private function makeBooking(array $overrides = []): Booking
    {
        return Booking::create(array_merge([
            'booking_number' => 'BKPAGE-'.uniqid(),
            'customer_id' => $this->customer->id,
            'vehicle_id' => null,
            'provider_source' => 'greenmotion',
            'provider_vehicle_id' => 'gwv-1',
            'provider_booking_ref' => null,
            'provider_metadata' => [],
            'vehicle_name' => 'Fiat 500 Hybrid',
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
            'pending_amount' => 85,
            'amount_paid' => 15,
            'booking_currency' => 'EUR',
            'payment_status' => 'partial',
            'booking_status' => 'confirmed',
        ], $overrides));
    }

    #[Test]
    public function bookings_list_provides_global_status_counts_and_can_cancel_flags(): void
    {
        $this->makeBooking(['booking_status' => 'confirmed']);
        $this->makeBooking(['booking_status' => 'reservation_failed']);
        $this->makeBooking(['booking_status' => 'cancelled']);

        $response = $this->actingAs($this->user)->get(route('profile.bookings.all', ['locale' => 'en']));

        $response->assertOk();
        $page = $response->viewData('page');
        $props = $page['props'];

        $this->assertSame(1, (int) $props['status_counts']['confirmed']);
        $this->assertSame(1, (int) $props['status_counts']['reservation_failed']);
        $this->assertSame(1, (int) $props['status_counts']['cancelled']);

        foreach ($props['bookings']['data'] as $booking) {
            $this->assertArrayHasKey('can_cancel', $booking);
            // External bookings without gateway metadata can never cancel via API.
            $this->assertFalse($booking['can_cancel']);
        }
    }

    #[Test]
    public function external_booking_details_have_empty_brand_and_no_fabricated_category(): void
    {
        $booking = $this->makeBooking();

        $response = $this->actingAs($this->user)->get(route('booking.show', ['locale' => 'en', 'id' => $booking->id]));

        $response->assertOk();
        $props = $response->viewData('page')['props'];

        $this->assertSame('', $props['vehicle']['brand']);
        $this->assertSame('Fiat 500 Hybrid', $props['vehicle']['model']);
        $this->assertNull($props['vehicle']['category']);
        $this->assertNull($props['vehicle']['transmission']);
        $this->assertFalse((bool) $props['booking']['can_cancel']);
    }

    #[Test]
    public function reservation_failed_booking_cannot_be_cancelled_but_confirmed_internal_can(): void
    {
        $failed = $this->makeBooking(['booking_status' => 'reservation_failed']);
        $internal = $this->makeBooking(['provider_source' => 'internal', 'booking_status' => 'confirmed']);

        $failedProps = $this->actingAs($this->user)
            ->get(route('booking.show', ['locale' => 'en', 'id' => $failed->id]))
            ->viewData('page')['props'];
        $internalProps = $this->actingAs($this->user)
            ->get(route('booking.show', ['locale' => 'en', 'id' => $internal->id]))
            ->viewData('page')['props'];

        $this->assertFalse((bool) $failedProps['booking']['can_cancel']);
        $this->assertTrue((bool) $internalProps['booking']['can_cancel']);
    }

    #[Test]
    public function legacy_booking_list_routes_redirect_to_unified_list(): void
    {
        foreach (['completed-bookings', 'confirmed-bookings', 'pending-bookings', 'customer/bookings'] as $path) {
            $this->actingAs($this->user)
                ->get("/en/{$path}")
                ->assertRedirect(route('profile.bookings.all', ['locale' => 'en']));
        }
    }
}
