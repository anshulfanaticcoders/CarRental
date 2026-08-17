<?php

namespace App\Notifications\Payment;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;

/**
 * Alerts an admin that a captured payment needs a manual refund or
 * reconciliation. Vrooem never refunds automatically — this is the visibility
 * layer so no owed refund lives only in a log line.
 */
class AdminManualRefundRequiredNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected ?string $paymentIntentId,
        protected string $reason,
        protected array $context = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /** Identifies the underlying incident, so retries of it collapse into one alert. */
    public function dedupeKey(): string
    {
        $subject = $this->paymentIntentId ?: ($this->context['session_id'] ?? '');

        return sha1($this->reason.'|'.$subject);
    }

    /**
     * Send at most one alert per incident per day.
     *
     * Stripe retries a failing webhook for ~3 days, and every retry reaches this
     * notification. On 2026-08-16 admin received five alerts in 2.5 hours despite
     * a 24h suppression window: one caller had no dedupe at all, and the other's
     * cache-based guard could not hold — a redeploy discards it, and prod's Redis
     * runs maxmemory-policy volatile-lru, which evicts exactly the kind of
     * TTL-bearing key it relied on. So dedupe reads the notifications TABLE, which
     * survives both, raw (no soft-delete scope) so clearing the bell cannot reopen
     * a flood.
     */
    public static function sendOnce(object $admin, self $notification): bool
    {
        $alreadySent = DB::table('notifications')
            ->where('type', self::class)
            ->where('notifiable_id', $admin->getKey())
            ->where('data->dedupe_key', $notification->dedupeKey())
            ->where('created_at', '>=', now()->subDay())
            ->exists();

        if ($alreadySent) {
            return false;
        }

        $admin->notify($notification);

        return true;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Action needed: manual refund/reconciliation required')
            ->greeting('Hello Admin,')
            ->line('A captured payment needs manual review — a booking could not be completed after the customer paid.')
            ->line('**Payment Intent:** '.($this->paymentIntentId ?: 'MISSING — check Stripe dashboard by session'))
            ->line('**Reason:** '.$this->reason);

        foreach ($this->context as $key => $value) {
            if (is_scalar($value) && (string) $value !== '') {
                $mail->line('**'.ucwords(str_replace('_', ' ', (string) $key)).':** '.$value);
            }
        }

        return $mail
            ->line('Verify the charge in Stripe and either complete the booking or refund the customer manually.')
            ->action('View Bookings', url('/customer-bookings'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Manual refund required'.($this->paymentIntentId ? ' ('.$this->paymentIntentId.')' : ''),
            'payment_intent_id' => $this->paymentIntentId,
            'dedupe_key' => $this->dedupeKey(),
            'reason' => $this->reason,
            'context' => $this->context,
            'role' => 'admin',
            'message' => 'A captured payment needs manual refund/reconciliation: '.$this->reason,
        ];
    }
}
