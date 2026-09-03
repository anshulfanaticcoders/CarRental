<?php

namespace App\Services;

use App\Exceptions\StripePaymentAlreadyCapturedException;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\User;
use App\Notifications\Payment\AdminAuthorizationReviewNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProviderBookingCancellationService
{
    /**
     * Cancel locally and, when a supplier reference exists, upstream as one
     * serialized operation. The reservation job uses this same lock key.
     *
     * @return array{success: bool, code?: string, message?: string, booking?: Booking}
     */
    public function cancel(
        int $bookingId,
        string $reason,
        string $auditActor = 'Customer',
        bool $supplierChecked = false
    ): array {
        $lock = Cache::lock("provider-booking-operation:{$bookingId}", 210);
        if (! $lock->block(15)) {
            return [
                'success' => false,
                'code' => 'operation_in_progress',
                'message' => 'A supplier operation is already in progress. Please try again shortly.',
            ];
        }

        try {
            $booking = Booking::find($bookingId);
            if (! $booking) {
                return ['success' => false, 'code' => 'not_found', 'message' => 'Booking not found.'];
            }

            $metadata = $booking->provider_metadata ?? [];
            $resumeAuthorizationRelease = $booking->booking_status === 'cancelled'
                && $booking->payment_status === 'authorized'
                && ! empty($metadata['cancellation_authorization_release_pending']);

            if ($booking->booking_status === 'cancelled' && ! $resumeAuthorizationRelease) {
                return ['success' => false, 'code' => 'already_cancelled', 'message' => 'Booking is already cancelled.'];
            }
            if (in_array($booking->booking_status, ['completed', 'rejected', 'expired'], true)) {
                return [
                    'success' => false,
                    'code' => 'not_cancellable',
                    'message' => 'A '.$booking->booking_status.' booking cannot be cancelled.',
                ];
            }

            $providerSource = strtolower((string) ($booking->provider_source ?? ''));
            $external = $providerSource !== '' && $providerSource !== 'internal';
            $supplierCancellationConfirmed = $resumeAuthorizationRelease;

            if ($resumeAuthorizationRelease) {
                // Supplier cancellation was already confirmed (or no supplier
                // reservation existed). Resume only the idempotent Stripe
                // authorization release; never call the supplier twice.
            } elseif ($external && empty($booking->provider_booking_ref)) {
                $outcomeUnknown = ! empty($metadata['reservation_manual_check'])
                    || ! empty($metadata['reservation_unknown_at']);
                if ($outcomeUnknown && ! $supplierChecked) {
                    return [
                        'success' => false,
                        'code' => 'supplier_outcome_unknown',
                        'message' => 'The supplier reservation outcome is unknown. Check and cancel it in the supplier portal before closing this booking.',
                    ];
                }

                if ($outcomeUnknown) {
                    $metadata = array_merge($metadata, [
                        'supplier_portal_checked_at' => now()->toIso8601String(),
                        'supplier_portal_checked_by' => $auditActor,
                        'supplier_portal_check_result' => 'no_reservation_found',
                    ]);
                    $booking->provider_metadata = $metadata;
                }

                $closeOutActor = str_starts_with($auditActor, 'Admin #') ? 'Admin' : $auditActor;
                $booking->notes = trim(($booking->notes ? $booking->notes."\n" : '')
                    .$closeOutActor.' close-out: cancelled without a supplier call because no provider reservation reference existed.'
                    .($outcomeUnknown ? ' Supplier portal was checked and no reservation was found.' : ''));
            } elseif ($external) {
                $gatewayBookingId = trim((string) ($metadata['gateway_booking_id'] ?? ''));
                $gatewaySupplierId = trim((string) ($metadata['gateway_supplier_id'] ?? $this->mapSupplierId($providerSource)));
                if ($gatewayBookingId === '' || $gatewaySupplierId === '') {
                    return [
                        'success' => false,
                        'code' => 'gateway_metadata_missing',
                        'message' => 'Provider gateway cancellation metadata is missing.',
                    ];
                }

                try {
                    $response = app(VrooemGatewayService::class)->cancelBooking(
                        $gatewayBookingId,
                        $gatewaySupplierId,
                        (string) $booking->provider_booking_ref,
                        $reason
                    );
                } catch (\Throwable $e) {
                    Log::error('Provider cancellation request failed', [
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage(),
                    ]);

                    return [
                        'success' => false,
                        'code' => 'gateway_failure',
                        'message' => 'Failed to cancel reservation with provider gateway.',
                    ];
                }

                if (! $this->cancellationSucceeded($response)) {
                    $status = is_array($response) ? ($response['status'] ?? null) : null;
                    Log::warning('Provider gateway returned an unsuccessful cancellation status', [
                        'booking_id' => $booking->id,
                        'gateway_status' => $status,
                        'gateway_response' => $response,
                    ]);

                    return [
                        'success' => false,
                        'code' => 'gateway_rejected',
                        'message' => 'The provider did not confirm cancellation. The booking remains active.',
                    ];
                }

                $booking->notes = trim(($booking->notes ? $booking->notes."\n" : '').'Gateway Cancel: confirmed by provider.');
                $supplierCancellationConfirmed = true;
            }

            // When a supplier reservation exists, cancel it first. Releasing
            // the card before the upstream cancellation is durable could leave
            // Vrooem liable for a live reservation with no collectible funds.
            if ($booking->payment_status === 'authorized') {
                $capturedDuringCancellation = false;
                try {
                    app(StripePaymentLifecycleService::class)->releaseAuthorization(
                        (string) $booking->stripe_payment_intent_id,
                        'release_booking_'.$booking->id
                    );
                } catch (StripePaymentAlreadyCapturedException) {
                    // Capture may have succeeded in Stripe while the local
                    // finalizer was interrupted. The supplier is already
                    // cancelled, so record the money and route it to refund.
                    $this->recordCapturedPaymentForCancellation($booking);
                    $capturedDuringCancellation = true;
                } catch (\Throwable $e) {
                    $releaseMetadata = array_merge($booking->provider_metadata ?? [], [
                        'reservation_manual_check' => true,
                        'authorization_release_error' => substr($e->getMessage(), 0, 500),
                        'authorization_release_failed_at' => now()->toIso8601String(),
                        'cancellation_authorization_release_pending' => true,
                    ]);
                    if ($supplierCancellationConfirmed) {
                        $releaseMetadata['supplier_cancellation_confirmed_at'] =
                            $releaseMetadata['supplier_cancellation_confirmed_at'] ?? now()->toIso8601String();
                    }
                    $booking->update([
                        'booking_status' => 'cancelled',
                        'cancellation_reason' => $reason,
                        'notes' => $booking->notes,
                        'provider_metadata' => $releaseMetadata,
                    ]);
                    $this->notifyAuthorizationReview($booking, $e->getMessage(), false);

                    return [
                        'success' => false,
                        'code' => 'authorization_release_failed',
                        'message' => 'The card authorization could not be released automatically. The booking needs support review.',
                    ];
                }

                if (! $capturedDuringCancellation) {
                    $releaseMetadata = $booking->provider_metadata ?? [];
                    unset(
                        $releaseMetadata['authorization_release_error'],
                        $releaseMetadata['authorization_release_failed_at'],
                        $releaseMetadata['cancellation_authorization_release_pending']
                    );
                    $booking->update([
                        'payment_status' => 'payment_cancelled',
                        'amount_paid' => 0,
                        'pending_amount' => 0,
                        'provider_metadata' => array_merge($releaseMetadata, [
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
                }
            }

            $booking->booking_status = 'cancelled';
            $booking->cancellation_reason = $reason;

            // A cancelled booking with captured money needs a refund. Flag it
            // for the rescue queue's manual-refund flow — never refund
            // automatically, and never downgrade an already-refunded state.
            $hasCapturedMoney = (float) $booking->amount_paid > 0
                || $booking->payments()->where('payment_status', 'succeeded')->exists();
            if ($hasCapturedMoney && ! in_array($booking->payment_status, ['refunded', 'refund_pending'], true)) {
                $booking->payment_status = 'refund_pending';
                $booking->provider_metadata = array_merge($booking->provider_metadata ?? [], [
                    'manual_refund_required' => true,
                    'refund_flagged_at' => now()->toIso8601String(),
                    'refund_flagged_by' => $auditActor,
                ]);
            }

            $booking->save();

            return ['success' => true, 'booking' => $booking];
        } finally {
            $lock->release();
        }
    }

    private function cancellationSucceeded(?array $response): bool
    {
        if (! is_array($response)) {
            return false;
        }

        $status = strtolower(trim((string) ($response['status'] ?? '')));

        return in_array($status, ['cancelled', 'canceled'], true)
            && ($response['success'] ?? true) !== false;
    }

    private function recordCapturedPaymentForCancellation(Booking $booking): void
    {
        $payment = $booking->payments()
            ->where('transaction_id', $booking->stripe_payment_intent_id)
            ->first();
        $capturedAmount = round((float) ($payment?->amount ?? 0), 2);

        $booking->update([
            'payment_status' => 'paid',
            'amount_paid' => $capturedAmount,
            'pending_amount' => 0,
            'provider_metadata' => array_merge($booking->provider_metadata ?? [], [
                'stripe_capture_detected_during_cancel_at' => now()->toIso8601String(),
            ]),
        ]);
        $payment?->update([
            'payment_status' => BookingPayment::STATUS_SUCCEEDED,
            'payment_date' => now(),
        ]);
        $booking->amounts?->update([
            'booking_paid_amount' => $capturedAmount,
            'booking_pending_amount' => 0,
            'admin_paid_amount' => $booking->amounts->admin_total_amount,
            'admin_pending_amount' => 0,
        ]);
    }

    private function notifyAuthorizationReview(Booking $booking, string $reason, bool $released): void
    {
        try {
            $admin = User::where('email', config('admin.email'))->first();
            if ($admin) {
                AdminAuthorizationReviewNotification::sendOnce(
                    $admin,
                    new AdminAuthorizationReviewNotification(
                        (string) ($booking->stripe_session_id ?: 'booking-'.$booking->id),
                        $reason,
                        $released,
                        $booking->id,
                        $booking->booking_number,
                    )
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Provider cancellation: failed to send authorization review alert', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function mapSupplierId(string $providerSource): string
    {
        return match ($providerSource) {
            'greenmotion' => 'green_motion',
            'adobe' => 'adobe_car',
            'okmobility' => 'ok_mobility',
            default => $providerSource,
        };
    }
}
