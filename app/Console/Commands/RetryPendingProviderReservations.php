<?php

namespace App\Console\Commands;

use App\Jobs\TriggerProviderReservationJob;
use App\Models\Booking;
use App\Models\StripeCheckoutPayload;
use App\Models\User;
use App\Notifications\Payment\AdminReservationManualCheckNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Self-healing sweep for PAID bookings whose supplier reservation never landed.
 *
 * TriggerProviderReservationJob retries on its own — but only if it runs. A queue
 * worker outage, a container restart mid-job, or a swallowed dispatch leaves the
 * booking "Provider pending" forever: money captured, no car reserved, and the
 * admin rescue queue is a read-only filter with no retry behind it. Bookings on
 * prod sat in exactly this state ("Reference missing") with nothing re-driving
 * them. This sweep is the retry.
 *
 * Safety: a booking is only picked up 3+ hours after its last activity — longer
 * than the job's full retry/backoff chain (~1h46m) — so the sweep can never race
 * a reservation attempt that is still in flight and double-book a customer.
 * After MAX_ATTEMPTS rescues it stops and routes the booking to manual review,
 * so a permanently broken booking becomes a human's problem, not an infinite loop.
 */
class RetryPendingProviderReservations extends Command
{
    protected $signature = 'bookings:retry-provider-reservations
        {--dry-run : List what would be retried without dispatching anything}';

    protected $description = 'Re-dispatch supplier reservations for paid bookings stuck without a provider reference.';

    /** Sweep rescues per booking before it is routed to manual review instead. */
    private const MAX_ATTEMPTS = 2;

    /** Must exceed the reservation job's full backoff chain (~1h46m). */
    private const QUIET_HOURS = 3;

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // 'pending' too: the admin provider-pending queue includes both, and a
        // pending+paid booking with no ref would otherwise never be swept —
        // permanently stuck with no retry path at all.
        $stuck = Booking::whereIn('booking_status', ['pending', 'confirmed'])
            ->whereNotNull('provider_source')
            ->where('provider_source', '!=', 'internal')
            ->whereIn('payment_status', ['partial', 'paid'])
            ->where(fn ($q) => $q->whereNull('provider_booking_ref')->orWhere('provider_booking_ref', ''))
            ->where('updated_at', '<=', now()->subHours(self::QUIET_HOURS))
            ->orderBy('id')
            ->get()
            ->reject(fn (Booking $b) => ! empty($b->provider_metadata['reservation_manual_check']))
            ->reject(fn (Booking $b) => ! empty($b->provider_metadata['manual_refund_required']));

        if ($stuck->isEmpty()) {
            $this->info('No stuck provider reservations.');

            return self::SUCCESS;
        }

        foreach ($stuck as $booking) {
            $attempts = (int) ($booking->provider_metadata['rescue_attempts'] ?? 0);
            $metadata = $this->reservationMetadataFor($booking);

            if ($attempts >= self::MAX_ATTEMPTS || $metadata === null) {
                $reason = $metadata === null
                    ? 'Booking is stuck without a supplier reservation and its checkout metadata could not be recovered for a retry.'
                    : 'Booking is still without a supplier reservation after '.$attempts.' automatic rescue attempts.';
                $this->line("#{$booking->booking_number}: routing to manual review — {$reason}");

                if (! $dryRun) {
                    $this->routeToManualReview($booking, $reason);
                }

                continue;
            }

            $this->line("#{$booking->booking_number}: re-dispatching reservation (rescue attempt ".($attempts + 1).')');

            if ($dryRun) {
                continue;
            }

            $booking->update([
                'provider_metadata' => array_merge($booking->provider_metadata ?? [], [
                    'rescue_attempts' => $attempts + 1,
                    'rescue_last_attempt_at' => now()->toIso8601String(),
                ]),
            ]);

            TriggerProviderReservationJob::dispatch($booking->id, $metadata);

            Log::warning('RetryPendingProviderReservations: re-dispatched stuck reservation', [
                'booking_id' => $booking->id,
                'rescue_attempt' => $attempts + 1,
            ]);
        }

        return self::SUCCESS;
    }

    /**
     * The reservation job needs the checkout metadata (gateway ids, driver
     * fields). It lives in stripe_checkout_payloads.full_metadata, keyed by the
     * booking's stripe_session_id.
     */
    private function reservationMetadataFor(Booking $booking): ?array
    {
        $payload = null;
        if (! empty($booking->stripe_session_id)) {
            $payload = StripeCheckoutPayload::where('stripe_session_id', $booking->stripe_session_id)->first();
        }

        // The session-id back-patch on the payload row is best-effort at
        // checkout; the payload id captured into provider_metadata at booking
        // creation is the fallback recovery path.
        if (! $payload && ! empty($booking->provider_metadata['checkout_payload_id'])) {
            $payload = StripeCheckoutPayload::find($booking->provider_metadata['checkout_payload_id']);
        }

        $metadata = $payload->payload['full_metadata'] ?? null;

        return is_array($metadata) && $metadata !== [] ? $metadata : null;
    }

    /**
     * Stop sweeping this booking and put it in front of a human. The flag also
     * removes it from every future sweep, so this fires once.
     */
    private function routeToManualReview(Booking $booking, string $reason): void
    {
        $booking->update([
            'provider_metadata' => array_merge($booking->provider_metadata ?? [], [
                'reservation_manual_check' => true,
                'reservation_unknown_at' => now()->toIso8601String(),
                'rescue_gave_up_reason' => $reason,
            ]),
        ]);

        try {
            $admin = User::where('email', config('admin.email'))->first();
            $admin?->notify(new AdminReservationManualCheckNotification($booking, $reason));
        } catch (\Throwable $e) {
            Log::warning('RetryPendingProviderReservations: failed to notify admin', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
