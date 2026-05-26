<?php

namespace App\Console\Commands;

use App\Mail\TrabberReportMail;
use App\Services\Trabber\TrabberReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;

class ExportTrabberDailyReport extends Command
{
    protected $signature = 'trabber:export-daily-report {--path= : Optional output path} {--send : Email the report to the configured Trabber recipient}';

    protected $description = 'Export the Trabber daily CSV report with last-year booking status data.';

    public function handle(TrabberReportService $reports): int
    {
        $path = $this->option('path') ?: storage_path('app/trabber/reports/daily/'.str_replace(
            '{date}',
            now()->toDateString(),
            (string) config('trabber.daily_report_filename')
        ));

        $csv = $reports->csvSince(now()->subYear());

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $csv);

        $this->info("Trabber daily report exported: {$path}");

        if ($this->option('send')) {
            $recipient = trim((string) config('trabber.report_recipient', ''));

            if ($recipient === '') {
                $this->error('TRABBER_REPORT_RECIPIENT is not configured.');

                return self::FAILURE;
            }

            Mail::to($recipient)->send(new TrabberReportMail('daily', $csv, basename($path)));
            $this->info("Trabber daily report emailed to {$recipient}");
        }

        return self::SUCCESS;
    }
}
