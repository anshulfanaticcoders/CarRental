<?php

namespace App\Services\Skyscanner;

class CarHireFieldStrategyService
{
    public function resolveSipp(array $quote): array
    {
        $explicit = $this->nullableString(data_get($quote, 'specs.sipp_code'));

        if ($explicit !== null) {
            return [
                'code' => strtoupper($explicit),
                'source' => 'explicit',
            ];
        }

        $categoryLetter = $this->resolveCategoryLetter($this->nullableString($quote['category'] ?? null));
        $transmissionLetter = $this->resolveTransmissionLetter($this->nullableString(data_get($quote, 'specs.transmission')));
        $airConditioningLetter = $this->resolveAirConditioningLetter(data_get($quote, 'specs.air_conditioning'));

        return [
            'code' => $categoryLetter . 'C' . $transmissionLetter . $airConditioningLetter,
            'source' => 'derived',
        ];
    }

    public function resolveSupplier(array $quote): array
    {
        $supplier = $quote['supplier'] ?? null;

        if (is_array($supplier)) {
            $code = $this->nullableString($supplier['code'] ?? null);
            $name = $this->nullableString($supplier['name'] ?? null);

            if ($code !== null && $name !== null) {
                return [
                    'code' => $code,
                    'name' => $name,
                    'source' => 'explicit',
                ];
            }
        }

        $providerCode = $this->nullableString($quote['provider_code'] ?? null)
            ?? $this->nullableString($quote['source'] ?? null)
            ?? 'unknown';

        return [
            'code' => $providerCode,
            'name' => $providerCode === 'internal' ? 'Vrooem Internal Fleet' : $this->headline($providerCode),
            'source' => 'derived',
        ];
    }

    public function resolveLocationIdentifier(array $location): array
    {
        $explicit = $this->nullableString($location['provider_location_id'] ?? null);

        if ($explicit !== null) {
            return [
                'id' => $explicit,
                'source' => 'explicit',
            ];
        }

        $name = strtolower($this->nullableString($location['name'] ?? null) ?? 'unknown-location');
        $latitude = $this->nullableString($location['latitude'] ?? null) ?? 'na';
        $longitude = $this->nullableString($location['longitude'] ?? null) ?? 'na';

        return [
            'id' => 'internal-' . substr(sha1($name . '|' . $latitude . '|' . $longitude), 0, 12),
            'source' => 'derived',
        ];
    }

    private function resolveCategoryLetter(?string $category): string
    {
        return match (strtolower((string) $category)) {
            'mini' => 'M',
            'economy' => 'E',
            'compact' => 'C',
            'intermediate' => 'I',
            'standard' => 'S',
            'fullsize', 'full-size' => 'F',
            'premium' => 'P',
            'luxury' => 'L',
            default => 'X',
        };
    }

    private function resolveTransmissionLetter(?string $transmission): string
    {
        return match (strtolower((string) $transmission)) {
            'automatic' => 'A',
            'manual' => 'M',
            default => 'X',
        };
    }

    private function resolveAirConditioningLetter(mixed $airConditioning): string
    {
        if ($airConditioning === null || $airConditioning === '') {
            return 'R';
        }

        $normalized = filter_var($airConditioning, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        return $normalized === false ? 'N' : 'R';
    }

    private function nullableString(mixed $value): ?string
    {
        $string = trim((string) ($value ?? ''));

        return $string === '' ? null : $string;
    }

    private function headline(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', strtolower($value))));
    }
}
