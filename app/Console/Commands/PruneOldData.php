<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PruneOldData extends Command
{
    protected $signature = 'data:prune
        {--force : Actually delete rows and files. Without this flag the command is a dry run that only prints counts.}
        {--only= : Comma-separated step names to run (chat, typing, activity, notifications, trabber_clicks, checkout_payloads, newsletter_logs, booking_holds, affiliate_sessions, affiliate_scans, contact, files)}';

    protected $description = 'Prune old chat, log, and tracking data per retention policy (dry-run by default)';

    private const CHUNK = 1000;

    private const CHAT_DAYS_AFTER_COMPLETION = 30;

    private const ACTIVITY_LOG_DAYS = 30;

    private const NOTIFICATIONS_KEEP_LATEST = 50;

    private const NOTIFICATIONS_READ_DAYS = 90;

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $only = array_filter(array_map('trim', explode(',', (string) $this->option('only'))));

        $steps = [
            'chat' => fn () => $this->pruneChatMessages($force),
            'typing' => fn () => $this->pruneTypingStatus($force),
            'activity' => fn () => $this->pruneActivityLogs($force),
            'notifications' => fn () => $this->pruneNotifications($force),
            'trabber_clicks' => fn () => $this->deleteWhere('trabber_clicks', fn ($q) => $q->where('expires_at', '<', now()->subDays(30)), $force),
            'checkout_payloads' => fn () => $this->deleteWhere('stripe_checkout_payloads', fn ($q) => $q->where('created_at', '<', now()->subDays(30)), $force),
            'newsletter_logs' => fn () => $this->deleteWhere('newsletter_campaign_logs', fn ($q) => $q->where('created_at', '<', now()->subDays(180)), $force),
            'booking_holds' => fn () => $this->deleteWhere('booking_holds', fn ($q) => $q->whereIn('status', ['expired', 'released', 'converted'])->where('updated_at', '<', now()->subDays(30)), $force),
            'affiliate_sessions' => fn () => $this->deleteWhere('affiliate_dashboard_sessions', fn ($q) => $q->where('expires_at', '<', now()->subDays(30)), $force),
            'affiliate_scans' => fn () => $this->deleteWhere('affiliate_customer_scans', fn ($q) => $q->whereNull('booking_id')->where('booking_completed', false)->where('created_at', '<', now()->subDays(180)), $force),
            'contact' => fn () => $this->deleteWhere('contact_submissions', fn ($q) => $q->where('status', 'responded')->where('updated_at', '<', now()->subYear()), $force),
            'files' => fn () => $this->pruneStorageFiles($force),
        ];

        if ($unknown = array_diff($only, array_keys($steps))) {
            $this->error('Unknown step(s): '.implode(', ', $unknown).'. Valid: '.implode(', ', array_keys($steps)));

            return self::FAILURE;
        }

        $mode = $force ? 'FORCE (deleting)' : 'DRY RUN (counts only — pass --force to delete)';
        $this->info("data:prune — {$mode}");

        $summary = [];
        foreach ($steps as $name => $step) {
            if ($only !== [] && ! in_array($name, $only, true)) {
                continue;
            }
            $count = $step();
            $summary[$name] = $count;
            $this->line(sprintf('  %-20s %s %d', $name, $force ? 'deleted' : 'would delete', $count));
        }

        Log::notice('data:prune summary', ['force' => $force] + $summary);

        return self::SUCCESS;
    }

    /**
     * Chat messages of bookings completed more than N days ago, including
     * their attachment/voice files on the upcloud disk. Raw delete on purpose:
     * Message uses SoftDeletes and soft-deleted rows must go too.
     */
    private function pruneChatMessages(bool $force): int
    {
        $completedBookings = DB::table('bookings')
            ->where('booking_status', 'completed')
            ->where('updated_at', '<', now()->subDays(self::CHAT_DAYS_AFTER_COMPLETION))
            ->select('id');

        $query = fn () => DB::table('messages')->whereIn('booking_id', $completedBookings);

        if (! $force) {
            return $query()->count();
        }

        $total = 0;
        do {
            $rows = $query()
                ->select('id', 'file_path', 'voice_note_path')
                ->orderBy('id')
                ->limit(self::CHUNK)
                ->get();

            if ($rows->isEmpty()) {
                break;
            }

            $files = $rows->flatMap(fn ($row) => [$row->file_path, $row->voice_note_path])
                ->filter()
                ->values()
                ->all();

            if ($files !== []) {
                try {
                    Storage::disk('upcloud')->delete($files);
                } catch (\Throwable $e) {
                    Log::warning('data:prune could not delete chat files from upcloud', ['error' => $e->getMessage()]);
                }
            }

            $total += DB::table('messages')->whereIn('id', $rows->pluck('id'))->delete();
        } while ($rows->count() === self::CHUNK);

        return $total;
    }

    private function pruneTypingStatus(bool $force): int
    {
        return $this->deleteWhere('chat_typing_status', function ($query) {
            $cutoff = now()->subDay();

            return $query->where(function ($group) use ($cutoff) {
                $group->where('last_activity_at', '<', $cutoff)
                    ->orWhere(fn ($noActivity) => $noActivity->whereNull('last_activity_at')->where('updated_at', '<', $cutoff));
            });
        }, $force);
    }

    private function pruneActivityLogs(bool $force): int
    {
        return $this->deleteWhere('activity_logs', fn ($q) => $q->where('created_at', '<', now()->subDays(self::ACTIVITY_LOG_DAYS)), $force);
    }

    /**
     * Delete read notifications older than 90 days, then trim each user to
     * the latest 50 (the mobile API reads at most 50).
     */
    private function pruneNotifications(bool $force): int
    {
        $total = $this->deleteWhere('notifications', fn ($q) => $q->whereNotNull('read_at')->where('created_at', '<', now()->subDays(self::NOTIFICATIONS_READ_DAYS)), $force);

        $overLimit = DB::table('notifications')
            ->select('notifiable_type', 'notifiable_id', DB::raw('COUNT(*) as total'))
            ->groupBy('notifiable_type', 'notifiable_id')
            ->having('total', '>', self::NOTIFICATIONS_KEEP_LATEST)
            ->get();

        foreach ($overLimit as $owner) {
            $ownerNotifications = fn () => DB::table('notifications')
                ->where('notifiable_type', $owner->notifiable_type)
                ->where('notifiable_id', $owner->notifiable_id);

            $keepIds = $ownerNotifications()
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(self::NOTIFICATIONS_KEEP_LATEST)
                ->pluck('id');

            $excess = fn () => $ownerNotifications()->whereNotIn('id', $keepIds);

            if (! $force) {
                $total += $excess()->count();

                continue;
            }

            do {
                $deleted = $excess()->limit(self::CHUNK)->delete();
                $total += $deleted;
            } while ($deleted === self::CHUNK);
        }

        return $total;
    }

    /**
     * On-disk report/audit artifacts that accumulate forever.
     */
    private function pruneStorageFiles(bool $force): int
    {
        $count = 0;
        $auditCutoff = now()->subDays(14)->getTimestamp();
        $reportCutoff = now()->subDays(60)->getTimestamp();

        foreach (['search-parity-audits', 'provider-price-audit'] as $dir) {
            $base = storage_path('app/'.$dir);
            if (! File::isDirectory($base)) {
                continue;
            }
            foreach (File::directories($base) as $subdir) {
                if (File::lastModified($subdir) < $auditCutoff) {
                    $count++;
                    if ($force) {
                        File::deleteDirectory($subdir);
                    }
                }
            }
        }

        $reportsDir = storage_path('app/trabber/reports/daily');
        if (File::isDirectory($reportsDir)) {
            foreach (File::files($reportsDir) as $file) {
                if ($file->getMTime() < $reportCutoff) {
                    $count++;
                    if ($force) {
                        File::delete($file->getPathname());
                    }
                }
            }
        }

        return $count;
    }

    /**
     * Count (dry run) or chunk-delete rows matching the condition.
     */
    private function deleteWhere(string $table, callable $condition, bool $force): int
    {
        if (! $force) {
            return $condition(DB::table($table))->count();
        }

        $total = 0;
        do {
            $deleted = $condition(DB::table($table))->limit(self::CHUNK)->delete();
            $total += $deleted;
        } while ($deleted === self::CHUNK);

        return $total;
    }
}
