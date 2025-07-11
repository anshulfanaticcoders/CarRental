<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login; // Added import
use Illuminate\Auth\Events\Logout; // Added import
use App\Listeners\LogSuccessfulLogin; // Added import
use App\Listeners\LogSuccessfulLogout; // Added import
use App\Events\BookingCompleted; // Added for Tapfiliate integration
use App\Listeners\ProcessTapfiliateBookingConversion; // Added for Tapfiliate integration

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        Login::class => [
            LogSuccessfulLogin::class,
        ],
        Logout::class => [
            LogSuccessfulLogout::class,
        ],
        BookingCompleted::class => [
            ProcessTapfiliateBookingConversion::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
