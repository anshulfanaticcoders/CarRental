<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\AwinService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAwinConversion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [10, 30, 60];

    public $bookingId;

    public $awc;

    public function __construct(int $bookingId, ?string $awc = null)
    {
        $this->bookingId = $bookingId;
        $this->awc = $awc;
    }

    public function handle(AwinService $awinService): void
    {
        if (! config('awin.enabled')) {
            return;
        }

        $booking = Booking::find($this->bookingId);
        if (! $booking) {
            Log::channel('awin')->warning('Awin Job: Booking not found', ['booking_id' => $this->bookingId]);

            return;
        }

        if (! in_array($booking->booking_status, ['confirmed', 'completed'], true)) {
            Log::channel('awin')->info('Awin Job: Skipping non-confirmed booking', [
                'booking_id' => $this->bookingId,
                'status' => $booking->booking_status,
            ]);

            return;
        }

        // Idempotency: a webhook replay re-dispatches this job; a conversion
        // must never be reported twice for one booking.
        if (! empty($booking->provider_metadata['awin_conversion_sent_at'])) {
            Log::channel('awin')->info('Awin Job: Conversion already sent, skipping', [
                'booking_id' => $this->bookingId,
            ]);

            return;
        }

        $result = $awinService->sendConversion($booking, $this->awc);

        if ($result['success'] ?? false) {
            // The server-side record of what we told Awin — the only artefact
            // reconciliation (and the refund reversal) can work from.
            $booking->update([
                'provider_metadata' => array_merge($booking->provider_metadata ?? [], [
                    'awin_conversion_sent_at' => now()->toIso8601String(),
                    'awin_conversion_amount' => AwinService::commissionAmountFor($booking),
                    'awin_test_mode' => (bool) config('awin.test_mode', true),
                ]),
            ]);

            return;
        }

        // Throw so $tries/$backoff actually retry HTTP-level failures — a
        // swallowed 500/429 from Awin used to lose the conversion permanently.
        throw new \RuntimeException(
            'Awin conversion failed for booking '.$booking->id.' (status '.($result['status'] ?? 'n/a').')'
        );
    }
}
