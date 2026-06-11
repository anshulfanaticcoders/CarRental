<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\StripeBookingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class TriggerProviderReservationJob implements ShouldQueue
{
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

        $service->triggerGatewayReservation($booking, (object) $this->metadata);
    }

    public function failed(Throwable $e): void
    {
        Log::error('TriggerProviderReservationJob exhausted retries - manual refund review required', [
            'booking_id' => $this->bookingId,
            'error' => $e->getMessage(),
        ]);

        $booking = Booking::find($this->bookingId);
        if (! $booking) {
            return;
        }

        // Mark booking as cancelled. No supplier reference means no valid reservation.
        $booking->update([
            'booking_status' => 'cancelled',
            'payment_status' => 'payment_cancelled',
            'cancellation_reason' => 'Payment cancelled: supplier did not confirm the reservation or return a provider reference.',
            'provider_metadata' => array_merge(
                $booking->provider_metadata ?? [],
                [
                    'manual_refund_required' => true,
                    'reservation_final_error' => substr($e->getMessage(), 0, 500),
                    'reservation_failed_at' => now()->toIso8601String(),
                ]
            ),
        ]);

        app(StripeBookingService::class)->recordManualRefundForFailedReservation(
            $booking->stripe_payment_intent_id,
            'External provider could not confirm reservation after retries'
        );
    }
}
