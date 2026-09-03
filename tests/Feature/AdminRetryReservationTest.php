<?php

namespace Tests\Feature;

use App\Jobs\TriggerProviderReservationJob;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\StripeCheckoutPayload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The 15-minute sweep is the automatic retry; this admin action is the
 * on-demand one, and the ONLY retry once a booking is flagged
 * reservation_manual_check (the flag removes it from every sweep).
 */
class AdminRetryReservationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function stuckBooking(array $overrides = []): Booking
    {
        $customerUser = User::factory()->create();
        $customer = Customer::create([
            'user_id' => $customerUser->id, 'first_name' => 'Stuck', 'last_name' => 'Customer',
            'email' => $customerUser->email, 'phone' => $customerUser->phone, 'driver_age' => 30,
        ]);

        return Booking::create(array_merge([
            'booking_number' => 'BKRETRY-'.uniqid(),
            'customer_id' => $customer->id,
            'provider_source' => 'greenmotion',
            'stripe_session_id' => 'cs_retry_'.uniqid(),
            'pickup_date' => now()->addDays(5), 'return_date' => now()->addDays(8),
            'pickup_time' => '09:00', 'return_time' => '09:00',
            'pickup_location' => 'Airport', 'return_location' => 'Airport',
            'plan' => 'BAS', 'total_days' => 3,
            'base_price' => 100, 'tax_amount' => 0, 'total_amount' => 100,
            'amount_paid' => 15, 'pending_amount' => 85,
            'payment_status' => 'partial', 'booking_status' => 'confirmed',
        ], $overrides));
    }

    private function payloadFor(Booking $booking): void
    {
        StripeCheckoutPayload::create([
            'stripe_session_id' => $booking->stripe_session_id,
            'payload' => ['full_metadata' => ['gateway_vehicle_id' => 'gw_retry', 'gateway_search_id' => 'gws-r']],
        ]);
    }

    #[Test]
    public function admin_can_queue_a_manual_retry_for_a_stuck_booking(): void
    {
        Queue::fake();
        $booking = $this->stuckBooking([
            'provider_metadata' => ['reservation_manual_check' => true],
            'supplier_resolution_notified_at' => now(),
        ]);
        $this->payloadFor($booking);

        $response = $this->actingAs($this->admin())
            ->from(route('customer-bookings.index'))
            ->post(route('customer-bookings.retry-reservation', ['id' => $booking->id]), [
                'supplier_checked' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Queue::assertPushed(TriggerProviderReservationJob::class,
            fn ($job) => $job->bookingId === $booking->id && $job->metadata['gateway_vehicle_id'] === 'gw_retry');

        $booking->refresh();
        $this->assertFalse((bool) $booking->provider_metadata['reservation_manual_check'],
            'A manual retry must lift the manual-check flag so the sweep can resume.');
        $this->assertNotNull($booking->provider_metadata['manual_retry_at']);
        $this->assertNull($booking->supplier_resolution_notified_at);
    }

    #[Test]
    public function an_unknown_outcome_retry_requires_supplier_confirmation(): void
    {
        // Unknown outcome = the supplier may already hold the reservation.
        // A blind redispatch would book a SECOND car for the same customer.
        Queue::fake();
        $booking = $this->stuckBooking([
            'provider_metadata' => ['reservation_manual_check' => true],
        ]);
        $this->payloadFor($booking);

        $this->actingAs($this->admin())
            ->post(route('customer-bookings.retry-reservation', ['id' => $booking->id]))
            ->assertSessionHas('error');

        Queue::assertNothingPushed();
    }

    #[Test]
    public function a_refunded_booking_cannot_be_retried(): void
    {
        Queue::fake();
        $booking = $this->stuckBooking(['payment_status' => 'refunded']);
        $this->payloadFor($booking);

        $this->actingAs($this->admin())
            ->post(route('customer-bookings.retry-reservation', ['id' => $booking->id]))
            ->assertSessionHas('error');

        Queue::assertNothingPushed();
    }

    #[Test]
    public function a_manual_retry_unfails_a_reservation_failed_booking(): void
    {
        // The reservation job (correctly) refuses reservation_failed bookings;
        // an explicit admin retry means "try again", so the status must go back
        // to confirmed for the job to act.
        Queue::fake();
        $booking = $this->stuckBooking(['booking_status' => 'reservation_failed']);
        $this->payloadFor($booking);

        $this->actingAs($this->admin())
            ->post(route('customer-bookings.retry-reservation', ['id' => $booking->id]))
            ->assertSessionHas('success');

        $this->assertSame('confirmed', $booking->refresh()->booking_status);
        Queue::assertPushed(TriggerProviderReservationJob::class);
    }

    #[Test]
    public function a_booking_with_a_supplier_reservation_cannot_be_retried(): void
    {
        Queue::fake();
        $booking = $this->stuckBooking(['provider_booking_ref' => 'VEM-777']);
        $this->payloadFor($booking);

        $this->actingAs($this->admin())
            ->post(route('customer-bookings.retry-reservation', ['id' => $booking->id]))
            ->assertSessionHas('error');

        Queue::assertNothingPushed();
    }

    #[Test]
    public function an_authorized_booking_with_a_supplier_reference_can_retry_capture_without_supplier_confirmation(): void
    {
        Queue::fake();
        $booking = $this->stuckBooking([
            'booking_status' => 'supplier_pending',
            'payment_status' => 'authorized',
            'provider_booking_ref' => 'EMR-CAPTURE-ONLY',
            'amount_paid' => 0,
            'provider_metadata' => [
                'reservation_manual_check' => true,
                'payment_capture_manual_check' => true,
                'capture_rescue_exhausted' => true,
            ],
        ]);
        $this->payloadFor($booking);

        $this->actingAs($this->admin())
            ->post(route('customer-bookings.retry-reservation', ['id' => $booking->id]))
            ->assertSessionHas('success');

        Queue::assertPushed(TriggerProviderReservationJob::class,
            fn ($job) => $job->bookingId === $booking->id);
        $metadata = $booking->fresh()->provider_metadata;
        $this->assertFalse((bool) $metadata['payment_capture_manual_check']);
        $this->assertFalse((bool) $metadata['capture_rescue_exhausted']);
    }

    #[Test]
    public function admin_can_record_a_found_supplier_reference_and_queue_capture_only(): void
    {
        Queue::fake();
        $admin = $this->admin();
        $booking = $this->stuckBooking([
            'booking_status' => 'supplier_pending',
            'payment_status' => 'authorized',
            'amount_paid' => 0,
            'provider_metadata' => [
                'reservation_manual_check' => true,
                'reservation_unknown_at' => now()->toIso8601String(),
            ],
        ]);
        $this->payloadFor($booking);

        $this->actingAs($admin)
            ->post(route('customer-bookings.record-supplier-reference', ['id' => $booking->id]), [
                'supplier_checked' => true,
                'supplier_reference' => 'EMR-PORTAL-9988',
            ])
            ->assertSessionHas('success');

        $booking->refresh();
        $this->assertSame('EMR-PORTAL-9988', $booking->provider_booking_ref);
        $this->assertTrue((bool) $booking->provider_metadata['payment_capture_manual_check']);
        $this->assertSame($admin->id, $booking->provider_metadata['manual_supplier_reference_recorded_by']);
        Queue::assertPushed(TriggerProviderReservationJob::class,
            fn ($job) => $job->bookingId === $booking->id);
    }

    #[Test]
    public function recording_a_supplier_reference_requires_a_confirmed_portal_check(): void
    {
        Queue::fake();
        $booking = $this->stuckBooking([
            'booking_status' => 'supplier_pending',
            'payment_status' => 'authorized',
            'amount_paid' => 0,
            'provider_metadata' => ['reservation_manual_check' => true],
        ]);
        $this->payloadFor($booking);

        $this->actingAs($this->admin())
            ->post(route('customer-bookings.record-supplier-reference', ['id' => $booking->id]), [
                'supplier_reference' => 'EMR-PORTAL-9988',
            ])
            ->assertSessionHasErrors('supplier_checked');

        $this->assertNull($booking->fresh()->provider_booking_ref);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function a_supplier_reference_rejects_multiline_audit_text(): void
    {
        Queue::fake();
        $booking = $this->stuckBooking([
            'booking_status' => 'supplier_pending',
            'payment_status' => 'authorized',
            'amount_paid' => 0,
            'provider_metadata' => ['reservation_manual_check' => true],
        ]);
        $this->payloadFor($booking);

        $this->actingAs($this->admin())
            ->post(route('customer-bookings.record-supplier-reference', ['id' => $booking->id]), [
                'supplier_checked' => true,
                'supplier_reference' => "EMR-7788\nFake audit event",
            ])
            ->assertSessionHasErrors('supplier_reference');

        $this->assertNull($booking->fresh()->provider_booking_ref);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function recording_a_supplier_reference_is_rejected_for_a_non_authorized_booking(): void
    {
        Queue::fake();
        $booking = $this->stuckBooking([
            'provider_metadata' => ['reservation_manual_check' => true],
        ]);
        $this->payloadFor($booking);

        $this->actingAs($this->admin())
            ->post(route('customer-bookings.record-supplier-reference', ['id' => $booking->id]), [
                'supplier_checked' => true,
                'supplier_reference' => 'EMR-PORTAL-9988',
            ])
            ->assertSessionHas('error');

        $this->assertNull($booking->fresh()->provider_booking_ref);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function unrecoverable_metadata_reports_an_error_instead_of_dispatching(): void
    {
        Queue::fake();
        $booking = $this->stuckBooking(); // no payload row

        $this->actingAs($this->admin())
            ->post(route('customer-bookings.retry-reservation', ['id' => $booking->id]))
            ->assertSessionHas('error');

        Queue::assertNothingPushed();
    }
}
