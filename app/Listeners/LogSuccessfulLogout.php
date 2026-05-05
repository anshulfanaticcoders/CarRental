<?php

namespace App\Listeners;

use App\Helpers\ActivityLogHelper;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\ChatStatus;
use Carbon\Carbon;

class LogSuccessfulLogout
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        if ($event->user) {
            $chatStatus = ChatStatus::where('user_id', $event->user->id)->first();
            if ($chatStatus) {
                $chatStatus->update([
                    'is_online' => false,
                    'last_logout_at' => Carbon::now(),
                ]);
            }

            ActivityLogHelper::log(
                'auth',
                'logout',
                "Logged out: {$event->user->email}",
                $event->user
            );
        }
    }
}
