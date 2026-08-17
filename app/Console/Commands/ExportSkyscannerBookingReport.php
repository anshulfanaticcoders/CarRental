<?php

namespace App\Console\Commands;

use App\Services\Skyscanner\CarHireReportingExportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Materialises the Skyscanner booking correlations (7-day cache window) into
 * a durable daily report file. The export service existed with zero callers —
 * correlations were collected and then evaporated with the cache.
 */
class ExportSkyscannerBookingReport extends Command
{
    protected $signature = 'skyscanner:export-bookings';

    protected $description = 'Write the correlated Skyscanner bookings to storage/app/skyscanner/ as a dated JSON report.';

    public function handle(CarHireReportingExportService $exportService): int
    {
        $rows = $exportService->exportRows();

        $path = 'skyscanner/bookings-'.now()->toDateString().'.json';
        Storage::disk('local')->put($path, json_encode([
            'generated_at' => now()->toIso8601String(),
            'row_count' => count($rows),
            'rows' => $rows,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info(count($rows).' correlated booking(s) written to storage/app/'.$path);

        return self::SUCCESS;
    }
}
