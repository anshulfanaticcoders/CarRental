<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DataPruneRetentionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function activity_logs_keep_latest_100_per_user_and_delete_old_overflow(): void
    {
        $user = User::factory()->create();
        // 105 old rows: floor keeps 100, the 5 oldest overflow are past the window.
        $this->seedActivity($user->id, 105, now()->subDays(30));

        $this->artisan('data:prune', ['--only' => 'activity', '--force' => true])->assertSuccessful();

        $this->assertSame(100, DB::table('activity_logs')->where('user_id', $user->id)->count());
    }

    #[Test]
    public function activity_overflow_within_window_is_kept(): void
    {
        $user = User::factory()->create();
        // 105 fresh rows: over the floor, but overflow is newer than the window → keep all.
        $this->seedActivity($user->id, 105, now());

        $this->artisan('data:prune', ['--only' => 'activity', '--force' => true])->assertSuccessful();

        $this->assertSame(105, DB::table('activity_logs')->where('user_id', $user->id)->count());
    }

    #[Test]
    public function low_activity_user_under_floor_is_untouched(): void
    {
        $user = User::factory()->create();
        $this->seedActivity($user->id, 50, now()->subDays(365));

        $this->artisan('data:prune', ['--only' => 'activity', '--force' => true])->assertSuccessful();

        $this->assertSame(50, DB::table('activity_logs')->where('user_id', $user->id)->count());
    }

    #[Test]
    public function notifications_keep_latest_100_per_owner_and_delete_old_overflow(): void
    {
        $user = User::factory()->create();
        $this->seedNotifications($user, 105, now()->subDays(30));

        $this->artisan('data:prune', ['--only' => 'notifications', '--force' => true])->assertSuccessful();

        $this->assertSame(100, DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->count());
    }

    #[Test]
    public function dry_run_reports_overflow_count_without_deleting(): void
    {
        $user = User::factory()->create();
        $this->seedActivity($user->id, 105, now()->subDays(30));

        $this->artisan('data:prune', ['--only' => 'activity'])
            ->expectsOutputToContain('would delete 5')
            ->assertSuccessful();

        // Nothing deleted in dry-run.
        $this->assertSame(105, DB::table('activity_logs')->where('user_id', $user->id)->count());
    }

    private function seedActivity(int $userId, int $count, $createdAt): void
    {
        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $rows[] = [
                'user_id' => $userId,
                'activity_type' => 'test',
                'activity_description' => 'seeded row '.$i,
                'logable_type' => User::class,
                'logable_id' => $userId,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('activity_logs')->insert($chunk);
        }
    }

    private function seedNotifications(User $user, int $count, $createdAt): void
    {
        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $rows[] = [
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\Test',
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode(['i' => $i]),
                'read_at' => null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('notifications')->insert($chunk);
        }
    }
}
