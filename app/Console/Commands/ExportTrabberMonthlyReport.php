<?php

namespace App\Console\Commands;

use App\Services\Trabber\TrabberReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportTrabberMonthlyReport extends Command
{
    protected $signature = 'trabber:export-monthly-report {--path= : Optional output path}';

    protected $description = 'Export the Trabber monthly CSV report with last-year booking status data.';

    public function handle(TrabberReportService $reports): int
    {
        $path = $this->option('path') ?: storage_path('app/trabber/reports/monthly/'.str_replace(
            '{month}',
            now()->format('Y-m'),
            (string) config('trabber.monthly_report_filename')
        ));

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $reports->csvSince(now()->subYear()));

        $this->info("Trabber monthly report exported: {$path}");

        return self::SUCCESS;
    }
}
