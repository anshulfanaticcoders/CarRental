<?php

namespace App\Console\Commands;

use App\Services\Trabber\TrabberReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportTrabberDailyReport extends Command
{
    protected $signature = 'trabber:export-daily-report {--path= : Optional output path}';

    protected $description = 'Export the Trabber daily CSV report with last-year booking status data.';

    public function handle(TrabberReportService $reports): int
    {
        $path = $this->option('path') ?: storage_path('app/trabber/reports/daily/'.str_replace(
            '{date}',
            now()->toDateString(),
            (string) config('trabber.daily_report_filename')
        ));

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $reports->csvSince(now()->subYear()));

        $this->info("Trabber daily report exported: {$path}");

        return self::SUCCESS;
    }
}
