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
        if (!config('awin.enabled')) {
            return;
        }

        $booking = Booking::find($this->bookingId);
        if (!$booking) {
            Log::channel('awin')->warning('Awin Job: Booking not found', ['booking_id' => $this->bookingId]);
            return;
        }

        if (!in_array($booking->booking_status, ['confirmed', 'completed'], true)) {
            Log::channel('awin')->info('Awin Job: Skipping non-confirmed booking', [
                'booking_id' => $this->bookingId,
                'status' => $booking->booking_status,
            ]);
            return;
        }

        $awinService->sendConversion($booking, $this->awc);
    }
}
