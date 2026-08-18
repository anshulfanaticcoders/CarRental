<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\User;
use App\Services\ProviderBookingCancellationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Cancelling a booking with captured money must land it in the manual-refund
 * flow — the money never silently stays with the platform.
 */
class CancellationRefundFlagTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function cancelling_a_paid_internal_booking_flags_the_refund(): void
    {
        $booking = $this->createInternalBooking(['amount_paid' => 120.50, 'payment_status' => 'paid']);

        $result = app(ProviderBookingCancellationService::class)->cancel($booking->id, 'Customer changed plans', 'Admin');

        $this->assertTrue($result['success']);
        $booking->refresh();
        $this->assertSame('cancelled', $booking->booking_status);
        $this->assertSame('refund_pending', $booking->payment_status);
        $this->assertTrue((bool) ($booking->provider_metadata['manual_refund_required'] ?? false));
        $this->assertSame('Admin', $booking->provider_metadata['refund_flagged_by'] ?? null);
    }

    #[Test]
    public function cancelling_an_unpaid_booking_does_not_flag_a_refund(): void
    {
        $booking = $this->createInternalBooking(['amount_paid' => 0, 'payment_status' => 'pending']);

        $result = app(ProviderBookingCancellationService::class)->cancel($booking->id, 'No show', 'Admin');

        $this->assertTrue($result['success']);
        $booking->refresh();
        $this->assertSame('cancelled', $booking->booking_status);
        $this->assertSame('pending', $booking->payment_status);
        $this->assertArrayNotHasKey('manual_refund_required', $booking->provider_metadata ?? []);
    }

    #[Test]
    public function an_already_refunded_booking_is_not_downgraded_to_refund_pending(): void
    {
        $booking = $this->createInternalBooking(['amount_paid' => 80, 'payment_status' => 'refunded']);

        $result = app(ProviderBookingCancellationService::class)->cancel($booking->id, 'Late cancellation', 'Admin');

        $this->assertTrue($result['success']);
        $this->assertSame('refunded', $booking->refresh()->payment_status);
    }

    private function createInternalBooking(array $overrides = []): Booking
    {
        $user = User::factory()->create();
        $customer = Customer::create([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => $user->email,
            'phone' => $user->phone,
            'driver_age' => 30,
        ]);

        return Booking::create(array_merge([
            'booking_number' => 'BKCXL-'.uniqid(),
            'customer_id' => $customer->id,
            'vehicle_id' => null,
            'provider_source' => 'internal',
            'provider_metadata' => [],
            'vehicle_name' => 'Internal Vehicle',
            'pickup_date' => now()->addDay(),
            'return_date' => now()->addDays(2),
            'pickup_time' => '09:00',
            'return_time' => '09:00',
            'pickup_location' => 'Airport',
            'return_location' => 'Airport',
            'plan' => 'BAS',
            'total_days' => 1,
            'base_price' => 100,
            'plan_price' => 0,
            'extra_charges' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 120.50,
            'pending_amount' => 0,
            'amount_paid' => 120.50,
            'booking_currency' => 'EUR',
            'payment_status' => 'paid',
            'booking_status' => 'confirmed',
        ], $overrides));
    }
}
