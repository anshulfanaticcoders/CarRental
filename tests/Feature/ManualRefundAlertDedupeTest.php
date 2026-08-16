<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\Payment\AdminManualRefundRequiredNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Stripe retries a failing webhook for ~3 days. On 2026-08-16 one failed session
 * produced five admin alerts in 2.5 hours: one code path had no dedupe at all,
 * and the other's cache guard lapsed. Both now dedupe on the notifications table,
 * which survives redeploys and Redis eviction.
 */
class ManualRefundAlertDedupeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'first_name' => 'Site',
            'last_name' => 'Admin',
            'email' => config('admin.email'),
            'phone' => '+27821110001',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    private function alert(string $reason = 'Paid Stripe session failed to create a booking (webhook error)', string $session = 'cs_live_aaa'): AdminManualRefundRequiredNotification
    {
        return new AdminManualRefundRequiredNotification(null, $reason, ['session_id' => $session]);
    }

    private function alertCount(User $admin): int
    {
        return DB::table('notifications')
            ->where('type', AdminManualRefundRequiredNotification::class)
            ->where('notifiable_id', $admin->getKey())
            ->count();
    }

    #[Test]
    public function a_retry_storm_on_one_session_produces_a_single_alert(): void
    {
        $admin = $this->admin();

        // Stripe's retry cadence on 2026-08-16: five deliveries in 2.5 hours.
        foreach (range(1, 5) as $ignored) {
            AdminManualRefundRequiredNotification::sendOnce($admin, $this->alert());
        }

        $this->assertSame(1, $this->alertCount($admin));
    }

    #[Test]
    public function sendonce_reports_whether_it_actually_sent(): void
    {
        $admin = $this->admin();

        $this->assertTrue(AdminManualRefundRequiredNotification::sendOnce($admin, $this->alert()));
        $this->assertFalse(AdminManualRefundRequiredNotification::sendOnce($admin, $this->alert()));
    }

    #[Test]
    public function a_genuinely_different_session_still_gets_its_own_alert(): void
    {
        // Suppressing real second incidents would be worse than the spam.
        $admin = $this->admin();

        AdminManualRefundRequiredNotification::sendOnce($admin, $this->alert(session: 'cs_live_aaa'));
        AdminManualRefundRequiredNotification::sendOnce($admin, $this->alert(session: 'cs_live_bbb'));

        $this->assertSame(2, $this->alertCount($admin));
    }

    #[Test]
    public function a_different_failure_reason_on_the_same_session_still_alerts(): void
    {
        $admin = $this->admin();

        AdminManualRefundRequiredNotification::sendOnce($admin, $this->alert());
        AdminManualRefundRequiredNotification::sendOnce(
            $admin,
            $this->alert(reason: 'Could not reserve vehicle inventory in time')
        );

        $this->assertSame(2, $this->alertCount($admin));
    }

    #[Test]
    public function an_unresolved_incident_alerts_again_after_a_day(): void
    {
        // Suppression must lapse eventually, or a still-broken booking goes quiet.
        $admin = $this->admin();

        AdminManualRefundRequiredNotification::sendOnce($admin, $this->alert());
        DB::table('notifications')->update(['created_at' => now()->subDays(2)]);
        AdminManualRefundRequiredNotification::sendOnce($admin, $this->alert());

        $this->assertSame(2, $this->alertCount($admin));
    }

    #[Test]
    public function clearing_the_notification_bell_does_not_reopen_the_flood(): void
    {
        // The bell soft-deletes. Dedupe reads the raw table on purpose.
        $admin = $this->admin();

        AdminManualRefundRequiredNotification::sendOnce($admin, $this->alert());
        DB::table('notifications')->update(['deleted_at' => now()]);
        AdminManualRefundRequiredNotification::sendOnce($admin, $this->alert());

        $this->assertSame(1, $this->alertCount($admin));
    }
}
