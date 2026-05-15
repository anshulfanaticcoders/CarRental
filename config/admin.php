<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Notification Recipient
    |--------------------------------------------------------------------------
    |
    | Email address that receives admin-tier notifications (new bookings, new
    | vendor sign-ups, payment failures, etc). Must match a real users.email
    | row or the notification will be silently dropped at dispatch time.
    |
    | Reading via config() instead of env() so `php artisan config:cache` works.
    */
    'email' => env('VITE_ADMIN_EMAIL', 'default@admin.com'),
];
