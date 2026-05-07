<?php

namespace App\Services\Vehicles\BulkImport;

use App\Models\User;
use Illuminate\Http\UploadedFile;

class BulkVehicleImportPreviewService
{
    public function __construct(
        private readonly BulkVehicleImportTemplateService $templateService,
        private readonly BulkVehicleImportRowValidator $rowValidator,
    ) {
    }

    public function preview(UploadedFile $file, User $user): array
    {
        return $this->previewPath((string) $file->getRealPath(), $user);
    }

    public function previewPath(string $path, User $user): array
    {
        $analysis = $this->analyzePath($path, $user);

        return $this->previewPayload($analysis);
    }

    public function analyzePath(string $path, User $user): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [
                'headers' => [],
                'normalized_headers' => [],
                'detected_mappings' => [],
                'missing_required_columns' => [],
                'unknown_columns' => [],
                'rows' => [],
                'valid_rows' => 0,
                'invalid_rows' => 0,
                'total_rows' => 0,
            ];
        }

        $headers = fgetcsv($handle) ?: [];
        $normalizedHeaders = array_map([$this, 'normalizeHeader'], $headers);
        $aliases = $this->normalizedAliases();
        $detectedMappings = $this->detectMappings($headers, $normalizedHeaders, $aliases);

        $missingRequiredColumns = [];
        foreach ($this->templateService->requiredColumns() as $column) {
            $target = $this->normalizeHeader($column['key']);
            if (!isset($detectedMappings[$target])) {
                $missingRequiredColumns[] = $column['key'];
            }
        }

        $requiredHeaders = array_map([$this, 'normalizeHeader'], $this->templateService->templateHeaders());
        $knownHeaders = array_unique(array_merge(
            $requiredHeaders,
            array_keys($aliases),
            ...array_values($aliases)
        ));

        $unknownColumns = array_values(array_filter(
            $headers,
            fn (string $header, int $index) => !in_array($normalizedHeaders[$index] ?? '', $knownHeaders, true),
            ARRAY_FILTER_USE_BOTH
        ));

        $rows = [];
        $totalRows = 0;
        $validRows = 0;
        $invalidRows = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $totalRows++;
            $rawRow = array_combine($headers, array_pad($row, count($headers), ''));
            $canonicalRow = $this->canonicalRow($detectedMappings, $rawRow);
            $validation = $this->rowValidator->validate($canonicalRow, $user, $totalRows + 1);

            if ($validation['valid']) {
                $validRows++;
            } else {
                $invalidRows++;
            }

            $rows[] = [
                'row_number' => $validation['row_number'],
                'raw' => $rawRow,
                'canonical' => $validation['normalized'],
                'issues' => $validation['issues'],
                'valid' => $validation['valid'],
                'resolved' => $validation['resolved'],
            ];
        }

        fclose($handle);

        return [
            'headers' => $headers,
            'normalized_headers' => $normalizedHeaders,
            'detected_mappings' => array_values($detectedMappings),
            'missing_required_columns' => $missingRequiredColumns,
            'unknown_columns' => $unknownColumns,
            'rows' => $rows,
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows,
            'total_rows' => $totalRows,
        ];
    }

    public function previewPayload(array $analysis): array
    {
        $maxPreviewRows = (int) config('vehicle_bulk_import.max_preview_rows', 5);

        return [
            'headers' => $analysis['headers'] ?? [],
            'normalized_headers' => $analysis['normalized_headers'] ?? [],
            'detected_mappings' => $analysis['detected_mappings'] ?? [],
            'missing_required_columns' => $analysis['missing_required_columns'] ?? [],
            'unknown_columns' => $analysis['unknown_columns'] ?? [],
            'preview_rows' => array_slice($analysis['rows'] ?? [], 0, $maxPreviewRows),
            'valid_rows' => $analysis['valid_rows'] ?? 0,
            'invalid_rows' => $analysis['invalid_rows'] ?? 0,
            'total_rows' => $analysis['total_rows'] ?? 0,
        ];
    }

    private function detectMappings(array $headers, array $normalizedHeaders, array $aliases): array
    {
        $mappings = [];

        foreach ($this->templateService->columns() as $column) {
            $target = $this->normalizeHeader($column['key']);
            $matchedHeader = null;

            foreach ($headers as $index => $header) {
                $normalizedHeader = $normalizedHeaders[$index] ?? '';
                if ($normalizedHeader === $target || in_array($normalizedHeader, $aliases[$target] ?? [], true)) {
                    $matchedHeader = $header;
                    break;
                }
            }

            if ($matchedHeader === null) {
                continue;
            }

            $mappings[$target] = [
                'key' => $column['key'],
                'label' => $column['label'] ?? $column['key'],
                'required' => (bool) ($column['required'] ?? false),
                'matched_header' => $matchedHeader,
            ];
        }

        return $mappings;
    }

    private function canonicalRow(array $detectedMappings, array $rawRow): array
    {
        $canonicalRow = [];

        foreach ($this->templateService->templateHeaders() as $columnKey) {
            $normalizedKey = $this->normalizeHeader($columnKey);
            $matchedHeader = $detectedMappings[$normalizedKey]['matched_header'] ?? null;
            $canonicalRow[$columnKey] = $matchedHeader ? ($rawRow[$matchedHeader] ?? '') : '';
        }

        return $canonicalRow;
    }

    private function normalizedAliases(): array
    {
        $aliases = [];

        foreach ($this->templateService->aliases() as $target => $list) {
            $aliases[$this->normalizeHeader($target)] = array_map(
                fn (string $alias) => $this->normalizeHeader($alias),
                $list
            );
        }

        return $aliases;
    }

    private function normalizeHeader(?string $value): string
    {
        return strtolower(trim((string) $value));
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
