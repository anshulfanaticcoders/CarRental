<?php

namespace App\Console;

use App\Console\Commands\AutoCompleteInternalBookings;
use App\Console\Commands\ExpireBookingHolds;
use App\Console\Commands\ExportTrabberDailyReport;
use App\Console\Commands\ExportTrabberMonthlyReport;
use App\Console\Commands\GeneratePublicSitemaps;
use App\Console\Commands\RefreshCurrencyRates;
use App\Console\Commands\RefreshMerchantFeed;
use App\Console\Commands\SendChatMessageReminders;
use App\Console\Commands\SendPendingBookingReminders;
use App\Console\Commands\SendScheduledNewsletterCampaigns;
use App\Console\Commands\UpdateGeoIpDatabase;
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
        $schedule->command(AutoCompleteInternalBookings::class)->everyMinute()->withoutOverlapping();
        $schedule->command(ExpireBookingHolds::class)->everyFiveMinutes()->withoutOverlapping();
        $schedule->command(SendChatMessageReminders::class)->everyFiveMinutes();
        $schedule->command(SendPendingBookingReminders::class)->twiceDaily();
        $schedule->command(RefreshCurrencyRates::class)->everyTwoHours();
        $schedule->command(RefreshMerchantFeed::class, ['awin'])->hourly()->withoutOverlapping();
        $schedule->command(GeneratePublicSitemaps::class)->daily()->withoutOverlapping();
        $schedule->command(ExportTrabberDailyReport::class, ['--send' => true])
            ->dailyAt('02:15')
            ->when(fn () => $this->shouldSendTrabberReports())
            ->withoutOverlapping();
        $schedule->command(ExportTrabberMonthlyReport::class, ['--send' => true])
            ->monthlyOn(1, '02:45')
            ->when(fn () => $this->shouldSendTrabberReports())
            ->withoutOverlapping();
        $schedule->command(SendScheduledNewsletterCampaigns::class)->everyMinute()->withoutOverlapping();
        $schedule->command(UpdateGeoIpDatabase::class)->weekly()->withoutOverlapping();
    }

    private function shouldSendTrabberReports(): bool
    {
        return (bool) config('trabber.enabled', false)
            && trim((string) config('trabber.report_recipient', '')) !== '';
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
