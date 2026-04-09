<?php

namespace App\Services\Skyscanner;

class CarHireQuoteValidationService
{
    public function validate(array $quote): array
    {
        $errors = [];

        if ($this->nullableString($quote['provider_vehicle_id'] ?? null) === null) {
            $errors[] = 'missing_provider_vehicle_id';
        }

        if ($this->nullableString($quote['display_name'] ?? null) === null) {
            $errors[] = 'missing_display_name';
        }

        if ($this->nullableString(data_get($quote, 'supplier.name')) === null) {
            $errors[] = 'missing_supplier_name';
        }

        if ($this->nullableString(data_get($quote, 'specs.sipp_code')) === null) {
            $errors[] = 'missing_sipp_code';
        }

        if ($this->nullableString(data_get($quote, 'pricing.currency')) === null) {
            $errors[] = 'missing_currency';
        }

        if (!is_numeric(data_get($quote, 'pricing.total_price'))) {
            $errors[] = 'missing_total_price';
        }

        if ($this->nullableString(data_get($quote, 'location.pickup.provider_location_id')) === null) {
            $errors[] = 'missing_pickup_location_id';
        }

        if ($this->nullableString(data_get($quote, 'location.dropoff.provider_location_id')) === null) {
            $errors[] = 'missing_dropoff_location_id';
        }

        return [
            'ready' => $errors === [],
            'errors' => $errors,
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $string = trim((string) ($value ?? ''));

        return $string === '' ? null : $string;
    }
}
