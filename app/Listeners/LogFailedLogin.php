<?php

namespace App\Listeners;

use App\Helpers\ActivityLogHelper;
use Illuminate\Auth\Events\Failed;

class LogFailedLogin
{
    public function handle(Failed $event): void
    {
        $email = $event->credentials['email'] ?? 'unknown';

        ActivityLogHelper::log(
            'auth',
            'login_failed',
            "Failed login attempt for: {$email}",
            null,
            ['email' => $email]
        );
    }
}
