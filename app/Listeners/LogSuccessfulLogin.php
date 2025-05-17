<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\ChatStatus;
use Carbon\Carbon;

class LogSuccessfulLogin
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
    public function handle(Login $event): void
    {
        if ($event->user) {
            ChatStatus::updateOrCreate(
                ['user_id' => $event->user->id],
                [
                    'is_online' => true,
                    'last_login_at' => Carbon::now(),
                    // 'last_logout_at' => null, // Optional: clear last logout time
                ]
            );
        }
    }
}
