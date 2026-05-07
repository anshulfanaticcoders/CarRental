<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VehicleCategory;
use App\Models\VendorLocation;
use App\Services\Vehicles\BulkImport\BulkVehicleImportExecutionService;
use App\Services\Vehicles\BulkImport\BulkVehicleImportPreviewService;
use App\Services\Vehicles\BulkImport\BulkVehicleImportStorageService;
use App\Services\Vehicles\BulkImport\BulkVehicleImportTemplateService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VendorBulkVehicleImportController extends Controller
{
    public function __construct(
        private readonly BulkVehicleImportTemplateService $templateService,
        private readonly BulkVehicleImportPreviewService $previewService,
        private readonly BulkVehicleImportStorageService $storageService,
        private readonly BulkVehicleImportExecutionService $executionService,
    ) {
    }

    public function index(Request $request)
    {
        $draft = $request->session()->get('bulk_vehicle_import_draft');

        return Inertia::render('Vendor/Vehicles/BulkImport/Index', [
            'templateColumns' => $this->templateService->columns(),
            'requiredColumns' => $this->templateService->requiredColumns(),
            'optionalColumns' => $this->templateService->optionalColumns(),
            'enumMap' => $this->templateService->enumMap(),
            'aliases' => $this->templateService->aliases(),
            'availableCategories' => VehicleCategory::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(fn (VehicleCategory $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ])
                ->values()
                ->all(),
            'vendorLocations' => VendorLocation::query()
                ->where('vendor_id', $request->user()->id)
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'iata_code', 'is_active'])
                ->map(fn (VendorLocation $location) => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'code' => $location->code,
                    'iata_code' => $location->iata_code,
                    'is_active' => (bool) $location->is_active,
                ])
                ->values()
                ->all(),
            'preview' => $request->session()->get('bulk_vehicle_import_preview'),
            'draftMeta' => $draft ? [
                'original_name' => $draft['original_name'] ?? null,
                'can_import' => $draft['can_import'] ?? false,
            ] : null,
            'flash' => $request->session()->only(['success', 'error', 'info', 'status']),
        ]);
    }

    public function downloadTemplate(Request $request): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="vrooem-bulk-vehicle-template.csv"',
        ];

        $sampleRows = $this->templateService->sampleRowsForUser($request->user());

        $callback = function () use ($sampleRows): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, $this->templateService->templateHeaders());
            foreach ($sampleRows as $sampleRow) {
                fputcsv($output, $sampleRow);
            }
            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:' . (int) config('vehicle_bulk_import.max_upload_kb', 4096),
        ]);

        $previousDraftPath = $request->session()->get('bulk_vehicle_import_draft.path');
        $this->storageService->delete($previousDraftPath);

        $storedPath = $this->storageService->store($request->file('csv_file'), $request->user());
        $analysis = $this->previewService->analyzePath($this->storageService->absolutePath($storedPath), $request->user());
        $preview = $this->previewService->previewPayload($analysis);
        $hasMissingColumns = $preview['missing_required_columns'] !== [];
        $hasInvalidRows = ($preview['invalid_rows'] ?? 0) > 0;
        $flashType = ($hasMissingColumns || $hasInvalidRows) ? 'error' : 'success';
        $flashMessage = match (true) {
            $hasMissingColumns => 'Some required columns are missing from your CSV. Review the preview and fix the file before importing.',
            $hasInvalidRows => 'The CSV structure was detected, but one or more rows still contain invalid values. Fix those rows before import.',
            default => 'CSV uploaded successfully. Review the detected mappings below before we enable full import.',
        };

        $request->session()->put('bulk_vehicle_import_preview', $preview);
        $request->session()->put('bulk_vehicle_import_draft', [
            'path' => $storedPath,
            'original_name' => $request->file('csv_file')->getClientOriginalName(),
            'can_import' => !$hasMissingColumns && !$hasInvalidRows && ($preview['total_rows'] ?? 0) > 0,
        ]);

        return redirect()
            ->route('vendor.vehicles.bulk-import.index', ['locale' => app()->getLocale()])
            ->with($flashType, $flashMessage);
    }

    public function import(Request $request)
    {
        $draft = $request->session()->get('bulk_vehicle_import_draft');
        $storedPath = $draft['path'] ?? null;

        if (!$this->storageService->exists($storedPath)) {
            return redirect()
                ->route('vendor.vehicles.bulk-import.index', ['locale' => app()->getLocale()])
                ->with('error', 'Your bulk import draft has expired. Please upload the CSV again.');
        }

        $analysis = $this->previewService->analyzePath($this->storageService->absolutePath($storedPath), $request->user());
        $preview = $this->previewService->previewPayload($analysis);

        try {
            $result = $this->executionService->import($analysis, $request->user());
        } catch (RuntimeException $exception) {
            $request->session()->put('bulk_vehicle_import_preview', $preview);
            $request->session()->put('bulk_vehicle_import_draft', array_merge($draft ?? [], [
                'can_import' => false,
            ]));

            return redirect()
                ->route('vendor.vehicles.bulk-import.index', ['locale' => app()->getLocale()])
                ->with('error', $exception->getMessage());
        }

        $this->storageService->delete($storedPath);
        $request->session()->forget(['bulk_vehicle_import_preview', 'bulk_vehicle_import_draft']);

        return redirect()
            ->route('current-vendor-vehicles.index', ['locale' => app()->getLocale()])
            ->with('message', $result['created_count'].' vehicle(s) imported successfully.')
            ->with('type', 'success');
    }
}
