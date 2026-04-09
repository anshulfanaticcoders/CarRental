<?php

namespace App\Services\Skyscanner;

class CarHireInventoryAuditService
{
    /**
     * Minimum fields needed for our first Skyscanner-ready internal quote shape.
     */
    private const REQUIRED_FIELDS = [
        'brand',
        'model',
        'image',
        'pricing.currency',
        'pricing.total_price',
        'specs.transmission',
        'specs.fuel',
        'specs.seating_capacity',
        'specs.doors',
        'location.pickup.provider_location_id',
        'location.pickup.name',
        'policies.fuel_policy',
        'policies.mileage_policy',
        'policies.cancellation',
    ];

    public function auditVehicle(array $vehicle): array
    {
        $missingFields = [];

        foreach (self::REQUIRED_FIELDS as $field) {
            if (!$this->hasValue($vehicle, $field)) {
                $missingFields[] = $field;
            }
        }

        return [
            'ready' => $missingFields === [],
            'missing_fields' => $missingFields,
        ];
    }

    public function auditVehicles(array $vehicles): array
    {
        $reports = [];
        $ready = 0;

        foreach (array_values($vehicles) as $index => $vehicle) {
            $report = $this->auditVehicle($vehicle);

            if ($report['ready']) {
                $ready++;
            }

            $reports[] = [
                'index' => $index + 1,
                'report' => $report,
            ];
        }

        $total = count($vehicles);

        return [
            'total' => $total,
            'ready' => $ready,
            'not_ready' => $total - $ready,
            'vehicles' => $reports,
        ];
    }

    private function hasValue(array $payload, string $path): bool
    {
        $segments = explode('.', $path);
        $value = $payload;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return false;
            }

            $value = $value[$segment];
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            return $value !== [];
        }

        return $value !== null;
    }
}
