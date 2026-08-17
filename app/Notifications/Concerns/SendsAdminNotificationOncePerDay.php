<?php

namespace App\Notifications\Concerns;

use Illuminate\Support\Facades\DB;

trait SendsAdminNotificationOncePerDay
{
    abstract public function dedupeKey(): string;

    /** Send one database/mail alert per incident in a rolling 24-hour window. */
    public static function sendOnce(object $admin, self $notification): bool
    {
        $alreadySent = DB::table('notifications')
            ->where('type', static::class)
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
}
