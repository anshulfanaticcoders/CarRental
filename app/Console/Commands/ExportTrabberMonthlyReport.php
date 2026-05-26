<?php

namespace App\Console\Commands;

use App\Mail\TrabberReportMail;
use App\Services\Trabber\TrabberReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;

class ExportTrabberMonthlyReport extends Command
{
    protected $signature = 'trabber:export-monthly-report {--path= : Optional output path} {--send : Email the report to the configured Trabber recipient}';

    protected $description = 'Export the Trabber monthly CSV report with last-year booking status data.';

    public function handle(TrabberReportService $reports): int
    {
        $path = $this->option('path') ?: storage_path('app/trabber/reports/monthly/'.str_replace(
            '{month}',
            now()->format('Y-m'),
            (string) config('trabber.monthly_report_filename')
        ));

        $csv = $reports->csvSince(now()->subYear());

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $csv);

        $this->info("Trabber monthly report exported: {$path}");

        if ($this->option('send')) {
            $recipient = trim((string) config('trabber.report_recipient', ''));

            if ($recipient === '') {
                $this->error('TRABBER_REPORT_RECIPIENT is not configured.');

                return self::FAILURE;
            }

            Mail::to($recipient)->send(new TrabberReportMail('monthly', $csv, basename($path)));
            $this->info("Trabber monthly report emailed to {$recipient}");
        }

        return self::SUCCESS;
    }
}
