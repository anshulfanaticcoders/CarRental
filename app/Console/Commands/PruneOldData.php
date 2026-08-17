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

    /** Keep the newest N rows per owner as a floor, then trim overflow by age. */
    private const KEEP_LATEST = 100;

    /** Overflow beyond the keep-floor is deleted once older than this. */
    private const OVERFLOW_DAYS = 7;

    /** Hard age cap: rows older than this are deleted even if within the keep-floor. */
    private const HARD_AGE_DAYS = 90;

    private const CONTACT_RESPONDED_DAYS = 30;

    private const CHECKOUT_PAYLOAD_DAYS = 7;

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
            'checkout_payloads' => fn () => $this->deleteWhere('stripe_checkout_payloads', fn ($q) => $q
                ->whereIn('fulfilment_status', ['fulfilled', 'expired'])
                ->where('created_at', '<', now()->subDays(self::CHECKOUT_PAYLOAD_DAYS)), $force),
            'newsletter_logs' => fn () => $this->deleteWhere('newsletter_campaign_logs', fn ($q) => $q->where('created_at', '<', now()->subDays(180)), $force),
            'booking_holds' => fn () => $this->deleteWhere('booking_holds', fn ($q) => $q->whereIn('status', ['expired', 'released', 'converted'])->where('updated_at', '<', now()->subDays(30)), $force),
            'affiliate_sessions' => fn () => $this->deleteWhere('affiliate_dashboard_sessions', fn ($q) => $q->where('expires_at', '<', now()->subDays(30)), $force),
            'affiliate_scans' => fn () => $this->deleteWhere('affiliate_customer_scans', fn ($q) => $q->whereNull('booking_id')->where('booking_completed', false)->where('created_at', '<', now()->subDays(180)), $force),
            'contact' => fn () => $this->pruneContactSubmissions($force),
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

    /**
     * Keep the latest 100 activity rows per user (and per system/NULL group),
     * deleting only overflow older than the retention window.
     */
    private function pruneActivityLogs(bool $force): int
    {
        return $this->pruneWithFloor('activity_logs', ['user_id'], $force);
    }

    /**
     * Keep the latest 100 notifications per owner (the bell/mobile list),
     * deleting only overflow older than the retention window.
     */
    private function pruneNotifications(bool $force): int
    {
        return $this->pruneWithFloor('notifications', ['notifiable_type', 'notifiable_id'], $force);
    }

    /**
     * Keep the latest 100 contact submissions as a floor, then delete only
     * already-responded ones older than the retention window. Open/unanswered
     * submissions are never deleted.
     */
    private function pruneContactSubmissions(bool $force): int
    {
        $keepIds = DB::table('contact_submissions')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::KEEP_LATEST)
            ->pluck('id');

        return $this->deleteWhere('contact_submissions', fn ($q) => $q
            ->where('status', 'responded')
            ->where('updated_at', '<', now()->subDays(self::CONTACT_RESPONDED_DAYS))
            ->whereNotIn('id', $keepIds), $force);
    }

    /**
     * Retain the newest KEEP_LATEST rows per owner group as a floor, then delete
     * rows beyond that floor once they are older than OVERFLOW_DAYS. The floor
     * guarantees a low-activity owner never loses recent history while a
     * high-volume owner's growth stays bounded. NULL owner values are treated as
     * their own group.
     *
     * @param  array<int, string>  $ownerColumns
     */
    private function pruneWithFloor(string $table, array $ownerColumns, bool $force): int
    {
        // Hard age cap first: anything older than HARD_AGE_DAYS goes regardless of
        // the keep-floor, so genuinely old rows do not linger for low-activity owners.
        $total = $this->deleteWhere(
            $table,
            fn ($q) => $q->where('created_at', '<', now()->subDays(self::HARD_AGE_DAYS)),
            $force
        );

        $cutoff = now()->subDays(self::OVERFLOW_DAYS);

        $groups = DB::table($table)
            ->select($ownerColumns)
            ->selectRaw('COUNT(*) as total')
            ->groupBy($ownerColumns)
            ->having('total', '>', self::KEEP_LATEST)
            ->get();

        foreach ($groups as $group) {
            $scope = function () use ($table, $ownerColumns, $group) {
                $query = DB::table($table);
                foreach ($ownerColumns as $column) {
                    $value = $group->{$column};
                    $value === null ? $query->whereNull($column) : $query->where($column, $value);
                }

                return $query;
            };

            $keepIds = $scope()
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(self::KEEP_LATEST)
                ->pluck('id');

            $overflow = fn () => $scope()
                ->whereNotIn('id', $keepIds)
                ->where('created_at', '<', $cutoff);

            if (! $force) {
                $total += $overflow()->count();

                continue;
            }

            do {
                $deleted = $overflow()->limit(self::CHUNK)->delete();
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
