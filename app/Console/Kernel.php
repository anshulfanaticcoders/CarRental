<?php

namespace App\Console;

use App\Console\Commands\SendChatMessageReminders;
use App\Console\Commands\SendPendingBookingReminders;
use App\Console\Commands\GreenMotionLocationsUpdateCommand; // Import the new command
use App\Console\Commands\GeneratePublicSitemaps;
use App\Console\Commands\MigrateSeoTargets;
use App\Console\Commands\UpdateUnifiedLocationsCommand;
use App\Console\Commands\RefreshCurrencyRates;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command(SendChatMessageReminders::class)->everyFiveMinutes();
        $schedule->command(SendPendingBookingReminders::class)->twiceDaily();
        $schedule->command(GreenMotionLocationsUpdateCommand::class)->daily(); // Schedule the new command
        $schedule->command(UpdateUnifiedLocationsCommand::class)->daily(); 
        $schedule->command(RefreshCurrencyRates::class)->everyTwoHours();
        $schedule->command(GeneratePublicSitemaps::class)->daily()->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
