<?php

namespace App\Jobs;

use App\Models\ApiBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Tells the partner's platform about OUR side's booking changes (admin or
 * vendor confirm/cancel/complete, auto-expiry). Before this, a booking we
 * cancelled stayed 'confirmed' on the partner's site until they happened to
 * poll — their customer flew in to a cancelled reservation.
 *
 * Signed with X-Vrooem-Signature = HMAC-SHA256(body, consumer webhook_secret).
 */
class SendPartnerBookingWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    public int $timeout = 30;

    /** Backoff in seconds — 30s, 2m, 10m, 1h. */
    public array $backoff = [30, 120, 600, 3600];

    public function __construct(
        public int $apiBookingId,
        public string $event,
        public array $snapshot = [],
    ) {}

    public function handle(): void
    {
        $booking = ApiBooking::with('consumer')->find($this->apiBookingId);
        if (! $booking || ! $booking->consumer) {
            return;
        }

        $url = trim((string) $booking->consumer->webhook_url);
        if ($url === '') {
            return; // partner has not registered a callback — polling only
        }

        $transition = $this->snapshot ?: [
            'booking_number' => $booking->booking_number,
            'status' => $booking->status,
            'cancellation_reason' => $booking->cancellation_reason,
            'cancellation_fee' => $booking->cancellation_fee !== null ? (float) $booking->cancellation_fee : null,
            'currency' => $booking->currency,
            'is_test' => (bool) $booking->is_test,
            'transitioned_at' => $booking->updated_at?->toIso8601String(),
        ];
        $payload = json_encode([
            'event' => $this->event,
            ...$transition,
            'sent_at' => now()->toIso8601String(),
        ]);

        $signature = hash_hmac('sha256', $payload, (string) $booking->consumer->webhook_secret);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Vrooem-Signature' => $signature,
            'X-Vrooem-Event' => $this->event,
        ])->timeout(15)->withBody($payload, 'application/json')->post($url);

        if (! $response->successful()) {
            // Throw so $tries/$backoff retry — the partner's platform showing
            // a stale status is exactly what this job exists to prevent.
            throw new \RuntimeException(
                "Partner webhook to consumer {$booking->consumer->id} failed with HTTP {$response->status()}"
            );
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendPartnerBookingWebhook exhausted retries — partner has a stale booking status', [
            'api_booking_id' => $this->apiBookingId,
            'event' => $this->event,
            'error' => $e->getMessage(),
        ]);
    }
}
