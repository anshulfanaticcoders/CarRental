<?php

namespace Tests\Feature;

use App\Exceptions\ReservationOutcomeUnknownException;
use App\Jobs\TriggerProviderReservationJob;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The provider leg runs AFTER the customer has paid, so its failure modes are
 * expensive: telling a customer with a real supplier reservation that it failed,
 * or reserving a second car because a retry could not tell the difference.
 */
class ProviderReservationSafetyTest extends TestCase
{
    use RefreshDatabase;

    private function paidBooking(array $overrides = []): Booking
    {
        $user = User::create([
            'first_name' => 'Paid', 'last_name' => 'Customer',
            'email' => 'paid.customer@example.com', 'phone' => '+27829995555',
            'password' => bcrypt('password'), 'role' => 'customer', 'status' => 'active',
        ]);
        $customer = Customer::create([
            'user_id' => $user->id, 'first_name' => 'Paid', 'last_name' => 'Customer',
            'email' => 'paid.customer@example.com', 'phone' => '+27829995555',
        ]);

        return Booking::create(array_merge([
            'booking_number' => 'BK-PROVIDER-SAFETY',
            'customer_id' => $customer->id,
            'provider_source' => 'greenmotion',
            'pickup_date' => now()->addDays(10),
            'return_date' => now()->addDays(13),
            'pickup_time' => '10:00',
            'return_time' => '10:00',
            'pickup_location' => 'Faro Airport',
            'return_location' => 'Faro Airport',
            'plan' => 'BAS',
            'total_days' => 3,
            'base_price' => 100,
            'tax_amount' => 0,
            'total_amount' => 100,
            'amount_paid' => 15,
            'pending_amount' => 85,
            'payment_status' => 'partial',
            'booking_status' => 'confirmed',
            'stripe_payment_intent_id' => 'pi_provider_safety',
        ], $overrides));
    }

    #[Test]
    public function a_confirmed_reservation_is_never_marked_failed_by_a_late_job_failure(): void
    {
        // The supplier confirmed and we stored the reference; the job then died on
        // its final attempt for an unrelated reason. Marking this reservation_failed
        // would tell a customer with a real car that it failed, and flag a valid
        // booking for refund.
        Notification::fake();
        $booking = $this->paidBooking(['provider_booking_ref' => 'VEM-1234567-7654321']);

        (new TriggerProviderReservationJob($booking->id, []))
            ->failed(new \RuntimeException('something threw after the reservation landed'));

        $booking->refresh();
        $this->assertSame('confirmed', $booking->booking_status);
        $this->assertSame('VEM-1234567-7654321', $booking->provider_booking_ref);
        $this->assertNull($booking->cancellation_reason);
        $this->assertArrayNotHasKey('manual_refund_required', $booking->provider_metadata ?? []);
    }

    #[Test]
    public function a_booking_with_no_reservation_is_still_held_for_manual_review(): void
    {
        // The guard above must not swallow the genuine failure path.
        Notification::fake();
        $booking = $this->paidBooking(['booking_number' => 'BK-PROVIDER-SAFETY-2']);

        (new TriggerProviderReservationJob($booking->id, []))
            ->failed(new \RuntimeException('supplier rejected the reservation'));

        $booking->refresh();
        $this->assertSame('reservation_failed', $booking->booking_status);
        $this->assertTrue((bool) ($booking->provider_metadata['manual_refund_required'] ?? false));
        $this->assertNotNull($booking->cancellation_reason);
    }

    #[Test]
    public function an_already_reserved_booking_is_not_sent_to_the_supplier_again(): void
    {
        // handle() must short-circuit on an existing reference, or a requeue books
        // a second car for the same customer.
        $booking = $this->paidBooking([
            'booking_number' => 'BK-PROVIDER-SAFETY-3',
            'provider_booking_ref' => 'VEM-9999999-1111111',
        ]);

        $service = \Mockery::mock(\App\Services\StripeBookingService::class);
        $service->shouldNotReceive('triggerGatewayReservation');

        (new TriggerProviderReservationJob($booking->id, []))->handle($service);

        $this->assertSame('VEM-9999999-1111111', $booking->refresh()->provider_booking_ref);
    }

    #[Test]
    public function a_booking_cancelled_mid_retry_is_not_reserved_at_the_supplier(): void
    {
        // Retries back off up to an hour; admin can cancel in that window. The
        // queued attempt must not reserve a real car for a dead booking.
        $booking = $this->paidBooking([
            'booking_number' => 'BK-PROVIDER-SAFETY-5',
            'booking_status' => 'cancelled',
        ]);

        $service = \Mockery::mock(\App\Services\StripeBookingService::class);
        $service->shouldNotReceive('triggerGatewayReservation');

        (new TriggerProviderReservationJob($booking->id, []))->handle($service);

        $this->assertSame('cancelled', $booking->refresh()->booking_status);
    }

    #[Test]
    public function an_unknown_outcome_is_not_retried_into_a_double_booking(): void
    {
        // ReservationOutcomeUnknownException means the supplier MIGHT hold a
        // reservation. handle() must fail the job outright rather than let the
        // queue retry, which would reserve a second car.
        $booking = $this->paidBooking(['booking_number' => 'BK-PROVIDER-SAFETY-4']);

        $service = \Mockery::mock(\App\Services\StripeBookingService::class);
        $service->shouldReceive('triggerGatewayReservation')
            ->once()
            ->andThrow(new ReservationOutcomeUnknownException('supplier timed out'));

        $job = \Mockery::mock(TriggerProviderReservationJob::class.'[fail]', [$booking->id, []]);
        $job->shouldAllowMockingProtectedMethods();
        $job->shouldReceive('fail')->once();

        $job->handle($service);

        $this->assertTrue(true, 'handle() routed the unknown outcome to fail() without retrying');
    }
}
