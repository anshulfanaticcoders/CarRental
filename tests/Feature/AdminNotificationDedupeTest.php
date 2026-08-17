<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\Concerns\SendsAdminNotificationOncePerDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminNotificationDedupeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function repeated_operational_alerts_for_one_incident_send_once_per_day(): void
    {
        $admin = User::create([
            'first_name' => 'Site',
            'last_name' => 'Admin',
            'email' => config('admin.email'),
            'phone' => '+27821110001',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        foreach (range(1, 5) as $ignored) {
            TestAdminIncidentNotification::sendOnce(
                $admin,
                new TestAdminIncidentNotification('booking-123')
            );
        }

        $this->assertSame(1, DB::table('notifications')->count());
    }
}

class TestAdminIncidentNotification extends Notification
{
    use SendsAdminNotificationOncePerDay;

    public function __construct(private readonly string $incident) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function dedupeKey(): string
    {
        return sha1($this->incident);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'dedupe_key' => $this->dedupeKey(),
            'message' => 'Test incident',
        ];
    }
}
