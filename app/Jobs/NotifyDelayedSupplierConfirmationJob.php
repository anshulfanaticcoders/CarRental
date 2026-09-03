<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Notifications\Booking\BookingPaymentReceivedCustomerNotification;
use App\Notifications\Concerns\DeliversToCustomer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NotifyDelayedSupplierConfirmationJob implements ShouldBeUnique, ShouldQueue
{
    use DeliversToCustomer;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 180];

    public int $uniqueFor = 600;

    public function __construct(public int $bookingId) {}

    public function uniqueId(): string
    {
        return (string) $this->bookingId;
    }

    public function handle(): void
    {
        $lock = Cache::lock("supplier-pending-notification:{$this->bookingId}", 30);
        if (! $lock->get()) {
            $this->release(10);

            return;
        }

        try {
            $booking = Booking::with(['customer.user', 'payments', 'vehicle'])->find($this->bookingId);
            if (! $booking || ! $this->shouldNotify($booking)) {
                return;
            }

            $this->deliverToCustomer(
                $booking->customer,
                new BookingPaymentReceivedCustomerNotification($booking, $booking->customer, $booking->vehicle)
            );
            $booking->update(['supplier_pending_notified_at' => now()]);
        } catch (\Throwable $e) {
            Log::warning('NotifyDelayedSupplierConfirmationJob failed', [
                'booking_id' => $this->bookingId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            $lock->release();
        }
    }

    private function shouldNotify(Booking $booking): bool
    {
        return $booking->booking_status === 'supplier_pending'
            && $booking->payment_status === 'authorized'
            && empty($booking->provider_booking_ref)
            && $booking->supplier_pending_notified_at === null
            && $booking->supplier_confirmation_deadline_at !== null
            && now()->greaterThanOrEqualTo($booking->supplier_confirmation_deadline_at);
    }
}
