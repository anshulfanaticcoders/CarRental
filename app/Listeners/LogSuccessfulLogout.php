<?php

namespace App\Listeners;

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
            // If no ChatStatus record exists, we might not need to do anything,
            // or we could create one with offline status, but typically a logout event
            // implies a previous login.
        }
    }
}
