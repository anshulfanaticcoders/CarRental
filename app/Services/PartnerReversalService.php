<?php

namespace App\Services;

use App\Models\Affiliate\AffiliateCommission;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\Payment\AdminPartnerReversalNeededNotification;
use Illuminate\Support\Facades\Log;

/**
 * When a booking dies AFTER partner conversions fired (Awin S2S at creation,
 * affiliate commission at creation), somebody has to un-pay them. Nothing did:
 * refunded bookings kept a validated Awin transaction and a pending affiliate
 * commission that the monthly sweep then approved and paid.
 *
 * Called from the Booking observer on every transition into a dead state.
 * Idempotent via the partner_reversal_done metadata marker.
 */
class PartnerReversalService
{
    public const DEAD_BOOKING_STATUSES = ['cancelled', 'rejected', 'expired', 'reservation_failed'];

    public const DEAD_PAYMENT_STATUSES = ['refunded'];

    public function reverseFor(Booking $booking, string $trigger): void
    {
        try {
            if (! empty($booking->provider_metadata['partner_reversal_done'])) {
                return;
            }

            $actions = [];

            $actions = array_merge($actions, $this->cancelAffiliateCommission($booking));
            $actions = array_merge($actions, $this->flagAwinVoid($booking));

            // Marker written even when nothing needed doing, so later
            // transitions (cancelled → refunded) don't re-run the checks.
            Booking::withoutEvents(function () use ($booking, $actions, $trigger) {
                $booking->update([
                    'provider_metadata' => array_merge($booking->provider_metadata ?? [], [
                        'partner_reversal_done' => now()->toIso8601String(),
                        'partner_reversal_trigger' => $trigger,
                        'partner_reversal_actions' => $actions,
                    ]),
                ]);
            });

            if ($actions !== []) {
                Log::info('PartnerReversalService: reversed partner conversions', [
                    'booking_id' => $booking->id,
                    'trigger' => $trigger,
                    'actions' => $actions,
                ]);

                $needsHuman = array_filter($actions, fn ($a) => str_starts_with($a, 'MANUAL'));
                if ($needsHuman !== []) {
                    $admin = User::where('email', config('admin.email'))->first();
                    if ($admin) {
                        AdminPartnerReversalNeededNotification::sendOnce(
                            $admin,
                            new AdminPartnerReversalNeededNotification($booking, array_values($needsHuman))
                        );
                    }
                }
            }
        } catch (\Throwable $e) {
            // Reversal must never block the cancellation/refund itself.
            Log::error('PartnerReversalService: reversal failed', [
                'booking_id' => $booking->id,
                'trigger' => $trigger,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** @return array<int, string> */
    private function cancelAffiliateCommission(Booking $booking): array
    {
        $commission = AffiliateCommission::where('booking_id', $booking->id)->first();
        if (! $commission) {
            return [];
        }

        if ($commission->status === 'paid') {
            // Money already left the bank — a human has to claw it back.
            $commission->update([
                'dispute_reason' => trim(($commission->dispute_reason ? $commission->dispute_reason.' | ' : '')
                    .'Booking '.$booking->booking_number.' was cancelled/refunded AFTER this commission was paid — clawback needed.'),
            ]);

            return ['MANUAL: affiliate commission '.$commission->id.' was already PAID — claw back '
                .$commission->commission_amount.' '.($commission->currency ?: 'EUR').' from the partner.'];
        }

        if (in_array($commission->status, ['pending', 'approved'], true)) {
            $commission->update([
                'status' => 'cancelled',
                'dispute_reason' => 'Auto-cancelled: booking '.$booking->booking_number.' was cancelled/refunded.',
            ]);

            return ['affiliate commission '.$commission->id.' auto-cancelled'];
        }

        return [];
    }

    /** @return array<int, string> */
    private function flagAwinVoid(Booking $booking): array
    {
        if (empty($booking->provider_metadata['awin_conversion_sent_at'])) {
            return [];
        }

        // The S2S pixel has no un-send; amendments happen in the Awin advertiser
        // dashboard (decline the transaction by our ref during validation).
        return ['MANUAL: decline the Awin transaction with ref '.$booking->booking_number
            .' in the Awin dashboard, or commission is paid on this refunded booking.'];
    }
}
