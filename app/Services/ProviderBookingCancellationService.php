<?php

namespace App\Services;

use App\Models\Booking;
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
    public function cancel(int $bookingId, string $reason, string $auditActor = 'Customer'): array
    {
        $lock = Cache::lock("provider-booking-operation:{$bookingId}", 180);
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

            if ($booking->booking_status === 'cancelled') {
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
            $metadata = $booking->provider_metadata ?? [];

            if ($external && empty($booking->provider_booking_ref)) {
                $outcomeUnknown = ! empty($metadata['reservation_manual_check'])
                    || ! empty($metadata['reservation_unknown_at']);
                if ($outcomeUnknown) {
                    return [
                        'success' => false,
                        'code' => 'supplier_outcome_unknown',
                        'message' => 'The supplier reservation outcome is unknown. Check and cancel it in the supplier portal before closing this booking.',
                    ];
                }

                $booking->notes = trim(($booking->notes ? $booking->notes."\n" : '')
                    .$auditActor.' close-out: cancelled without a supplier call because no provider reservation reference existed.');
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
