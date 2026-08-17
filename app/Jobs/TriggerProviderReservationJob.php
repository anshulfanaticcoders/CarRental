<?php

namespace App\Jobs;

use App\Exceptions\ReservationOutcomeUnknownException;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\Booking\ReservationFailedCustomerNotification;
use App\Notifications\Concerns\DeliversToCustomer;
use App\Notifications\Payment\AdminReservationFailedNotification;
use App\Services\StripeBookingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class TriggerProviderReservationJob implements ShouldQueue
{
    use DeliversToCustomer;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    public int $timeout = 120;

    /** Backoff in seconds — 1m, 5m, 10m, 30m, 1h. */
    public array $backoff = [60, 300, 600, 1800, 3600];

    public function __construct(
        public int $bookingId,
        public array $metadata,
    ) {}

    public function handle(StripeBookingService $service): void
    {
        $lock = Cache::lock("provider-booking-operation:{$this->bookingId}", 180);
        if (! $lock->block(15)) {
            throw new \RuntimeException('Could not acquire supplier booking operation lock.');
        }

        try {
            // Re-read eligibility only after acquiring the same mutex used by
            // cancellation. A job queued before cancellation/refund must not
            // reserve after the booking has become ineligible.
            $booking = Booking::find($this->bookingId);
            if (! $booking) {
                Log::warning('TriggerProviderReservationJob: booking not found', [
                    'booking_id' => $this->bookingId,
                ]);

                return;
            }
            if (! empty($booking->provider_booking_ref)) {
                Log::info('TriggerProviderReservationJob: reservation already complete', [
                    'booking_id' => $booking->id,
                    'provider_booking_ref' => $booking->provider_booking_ref,
                ]);

                return;
            }

            $eligiblePayment = in_array($booking->payment_status, ['partial', 'paid'], true);
            $terminalBooking = in_array($booking->booking_status, ['cancelled', 'rejected', 'expired', 'completed', 'reservation_failed'], true);
            $refundOrManualClose = in_array($booking->payment_status, ['refunded', 'refund_pending'], true)
                || ! empty($booking->provider_metadata['manual_refund_required']);
            if (! $eligiblePayment || $terminalBooking || $refundOrManualClose) {
                Log::info('TriggerProviderReservationJob: booking no longer eligible for reservation, skipping', [
                    'booking_id' => $booking->id,
                    'booking_status' => $booking->booking_status,
                    'payment_status' => $booking->payment_status,
                ]);

                return;
            }

            try {
                $service->triggerGatewayReservation($booking, (object) $this->metadata);
            } catch (ReservationOutcomeUnknownException $e) {
                // Supplier timed out; a reservation may already exist upstream.
                // Retrying would double-book, so fail now (no retries) and let
                // failed() leave the booking for manual reconciliation.
                Log::warning('TriggerProviderReservationJob: unknown reservation outcome, skipping retries', [
                    'booking_id' => $booking->id,
                ]);
                $this->fail($e);
            }
        } finally {
            $lock->release();
        }
    }

    public function failed(Throwable $e): void
    {
        $booking = Booking::find($this->bookingId);
        if (! $booking) {
            Log::error('TriggerProviderReservationJob failed - booking not found', [
                'booking_id' => $this->bookingId,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        // The supplier DID confirm — the throw came from something after the
        // reservation landed. Marking this reservation_failed would tell a customer
        // with a real car booked that it failed, and tell admin to refund it.
        if (! empty($booking->provider_booking_ref)) {
            Log::warning('TriggerProviderReservationJob: job failed AFTER a confirmed reservation, leaving booking intact', [
                'booking_id' => $booking->id,
                'provider_booking_ref' => $booking->provider_booking_ref,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        // Unknown outcome already routed to manual review; admin was notified by
        // the service. Do NOT cancel — the supplier may hold a reservation. Tell
        // the customer their booking is under review so they are never in the dark.
        if ($e instanceof ReservationOutcomeUnknownException
            || ! empty($booking->provider_metadata['reservation_manual_check'])) {
            Log::warning('TriggerProviderReservationJob: booking left for manual reconciliation (unknown outcome)', [
                'booking_id' => $booking->id,
            ]);
            $this->notifyCustomerReservationFailed($booking);

            return;
        }

        Log::error('TriggerProviderReservationJob exhausted retries - held for manual review', [
            'booking_id' => $this->bookingId,
            'error' => $e->getMessage(),
        ]);

        $finalError = substr($e->getMessage(), 0, 500);

        // The customer PAID. Never auto-cancel: hold the booking in
        // reservation_failed so an admin can rebook with the supplier or refund
        // manually. payment_status stays truthful (money captured, not refunded).
        $booking->update([
            'booking_status' => 'reservation_failed',
            'cancellation_reason' => 'Supplier could not confirm the reservation. Booking held for manual review.',
            'provider_metadata' => array_merge(
                $booking->provider_metadata ?? [],
                [
                    'manual_refund_required' => true,
                    'reservation_final_error' => $finalError,
                    'reservation_failed_at' => now()->toIso8601String(),
                ]
            ),
        ]);

        $this->notifyCustomerReservationFailed($booking);
        $this->notifyAdminReservationFailed($booking, $finalError);

        app(StripeBookingService::class)->recordManualRefundForFailedReservation(
            $booking->stripe_payment_intent_id,
            'External provider could not confirm reservation after retries'
        );
    }

    private function notifyCustomerReservationFailed(Booking $booking): void
    {
        try {
            $customer = $booking->customer;
            if (! $customer) {
                Log::warning('TriggerProviderReservationJob: no customer to notify', ['booking_id' => $booking->id]);

                return;
            }

            $this->deliverToCustomer(
                $customer,
                new ReservationFailedCustomerNotification($booking, $customer, $booking->vehicle)
            );
        } catch (Throwable $e) {
            Log::warning('TriggerProviderReservationJob: failed to notify customer of reservation failure', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notifyAdminReservationFailed(Booking $booking, string $reason): void
    {
        try {
            $admin = User::where('email', config('admin.email'))->first();
            if ($admin) {
                AdminReservationFailedNotification::sendOnce(
                    $admin,
                    new AdminReservationFailedNotification($booking, $reason)
                );
            }
        } catch (Throwable $e) {
            Log::warning('TriggerProviderReservationJob: failed to notify admin of reservation failure', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
