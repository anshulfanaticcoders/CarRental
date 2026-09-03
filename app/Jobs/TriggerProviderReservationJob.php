<?php

namespace App\Jobs;

use App\Exceptions\ReservationOutcomeUnknownException;
use App\Exceptions\StripePaymentAlreadyCapturedException;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\User;
use App\Notifications\Booking\ReservationFailedCustomerNotification;
use App\Notifications\Concerns\DeliversToCustomer;
use App\Notifications\Payment\AdminReservationFailedNotification;
use App\Notifications\Payment\AdminReservationManualCheckNotification;
use App\Services\StripeBookingService;
use App\Services\StripePaymentLifecycleService;
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

    public int $tries = 4;

    public int $timeout = 180;

    /** Fresh quote on every safe retry; resolve card authorizations quickly. */
    public array $backoff = [15, 60, 180];

    public function __construct(
        public int $bookingId,
        public array $metadata,
    ) {}

    public function handle(StripeBookingService $service, $paymentLifecycle = null): void
    {
        $lock = Cache::lock("provider-booking-operation:{$this->bookingId}", 210);
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
                if ($booking->payment_status === 'authorized') {
                    $paymentLifecycle ??= app(StripePaymentLifecycleService::class);
                    $service->finalizeAuthorizedSupplierBooking(
                        $booking,
                        (object) $this->metadata,
                        $paymentLifecycle
                    );

                    return;
                }

                Log::info('TriggerProviderReservationJob: reservation already complete', [
                    'booking_id' => $booking->id,
                    'provider_booking_ref' => $booking->provider_booking_ref,
                ]);

                return;
            }

            $eligiblePayment = in_array($booking->payment_status, ['authorized', 'partial', 'paid'], true);
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
                $reservationMetadata = $this->metadata;
                if ($booking->payment_status === 'authorized') {
                    $reservationMetadata = $service->refreshAuthorizedSupplierMetadata(
                        $booking,
                        $reservationMetadata
                    );
                }

                $service->triggerGatewayReservation($booking, (object) $reservationMetadata);

                $booking->refresh();
                if ($booking->payment_status === 'authorized' && ! empty($booking->provider_booking_ref)) {
                    $paymentLifecycle ??= app(StripePaymentLifecycleService::class);
                    $service->finalizeAuthorizedSupplierBooking(
                        $booking,
                        (object) $reservationMetadata,
                        $paymentLifecycle
                    );
                }
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
            if ($booking->payment_status === 'authorized') {
                $booking->update([
                    'provider_metadata' => array_merge($booking->provider_metadata ?? [], [
                        'reservation_manual_check' => true,
                        'payment_capture_manual_check' => true,
                        'payment_capture_last_error' => substr($e->getMessage(), 0, 500),
                        'payment_capture_failed_at' => now()->toIso8601String(),
                    ]),
                ]);
                $this->notifyAdminManualCheck(
                    $booking,
                    'Supplier reference '.$booking->provider_booking_ref
                    .' is saved, but Stripe capture did not complete. Do not retry the supplier reservation.'
                );
            }

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

        if ($booking->payment_status === 'authorized') {
            $releaseOutcome = $this->releaseFailedAuthorization($booking, $finalError);
            $booking = $booking->fresh();
            $this->notifyCustomerReservationFailed($booking);
            if ($releaseOutcome === 'failed') {
                $this->notifyAdminManualCheck(
                    $booking,
                    'The supplier definitively rejected the reservation, but the Stripe card authorization could not be released automatically.'
                );
            } else {
                $this->notifyAdminReservationFailed($booking, $finalError);
            }

            return;
        }

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

    private function releaseFailedAuthorization(Booking $booking, string $finalError): string
    {
        try {
            app(StripePaymentLifecycleService::class)->releaseAuthorization(
                (string) $booking->stripe_payment_intent_id,
                'release_booking_'.$booking->id
            );

            $booking->update([
                'booking_status' => 'reservation_failed',
                'payment_status' => 'payment_cancelled',
                'amount_paid' => 0,
                'pending_amount' => 0,
                'cancellation_reason' => 'Supplier could not confirm the reservation. Your card authorization was released.',
                'provider_metadata' => array_merge($booking->provider_metadata ?? [], [
                    'reservation_final_error' => $finalError,
                    'reservation_failed_at' => now()->toIso8601String(),
                    'stripe_authorization_released_at' => now()->toIso8601String(),
                ]),
            ]);
            $booking->payments()->where('payment_status', 'authorized')->update([
                'payment_status' => 'authorization_released',
            ]);
            $booking->amounts?->update([
                'booking_paid_amount' => 0,
                'booking_pending_amount' => 0,
                'admin_paid_amount' => 0,
                'admin_pending_amount' => 0,
            ]);

            return 'released';
        } catch (StripePaymentAlreadyCapturedException) {
            $payment = $booking->payments()
                ->where('transaction_id', $booking->stripe_payment_intent_id)
                ->first();
            $capturedAmount = round((float) ($payment?->amount ?? 0), 2);
            $booking->update([
                'booking_status' => 'reservation_failed',
                'payment_status' => 'refund_pending',
                'amount_paid' => $capturedAmount,
                'pending_amount' => 0,
                'cancellation_reason' => 'Supplier could not confirm the reservation. Captured payment requires a refund.',
                'provider_metadata' => array_merge($booking->provider_metadata ?? [], [
                    'manual_refund_required' => true,
                    'reservation_final_error' => $finalError,
                    'stripe_capture_detected_after_supplier_failure_at' => now()->toIso8601String(),
                ]),
            ]);
            $payment?->update(['payment_status' => BookingPayment::STATUS_SUCCEEDED]);
            $booking->amounts?->update([
                'booking_paid_amount' => $capturedAmount,
                'booking_pending_amount' => 0,
                'admin_paid_amount' => $booking->amounts->admin_total_amount,
                'admin_pending_amount' => 0,
            ]);

            return 'captured';
        } catch (Throwable $releaseError) {
            $booking->update([
                'provider_metadata' => array_merge($booking->provider_metadata ?? [], [
                    'reservation_manual_check' => true,
                    'authorization_release_error' => substr($releaseError->getMessage(), 0, 500),
                ]),
            ]);
            Log::critical('TriggerProviderReservationJob: Stripe authorization release needs manual review', [
                'booking_id' => $booking->id,
                'error' => $releaseError->getMessage(),
            ]);

            return 'failed';
        }
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

    private function notifyAdminManualCheck(Booking $booking, string $reason): void
    {
        try {
            $admin = User::where('email', config('admin.email'))->first();
            if ($admin) {
                AdminReservationManualCheckNotification::sendOnce(
                    $admin,
                    new AdminReservationManualCheckNotification($booking, $reason)
                );
            }
        } catch (Throwable $e) {
            Log::warning('TriggerProviderReservationJob: failed to notify admin of capture review', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
