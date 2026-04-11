<?php

namespace App\Services\Vehicles;

class SippCodeSuggestionService
{
    public function resolve(?string $explicitCode, array $attributes): ?string
    {
        $explicitCode = $this->normalizeCode($explicitCode);
        if ($explicitCode !== null) {
            return $explicitCode;
        }

        return $this->suggest($attributes);
    }

    public function suggest(array $attributes): ?string
    {
        $bodyStyle = $this->normalizeText($attributes['body_style'] ?? null);
        $transmission = $this->normalizeText($attributes['transmission'] ?? null);
        $fuel = $this->normalizeText($attributes['fuel'] ?? null);
        $airConditioning = $this->normalizeBool($attributes['air_conditioning'] ?? null);

        if ($bodyStyle === null || $transmission === null || $fuel === null || $airConditioning === null) {
            return null;
        }

        $categoryLetter = $this->resolveCategoryLetter($attributes, $bodyStyle);
        $typeLetter = $this->resolveTypeLetter($bodyStyle);
        $transmissionLetter = $transmission === 'automatic' ? 'A' : 'M';
        $fuelAcLetter = $this->resolveFuelAcLetter($fuel, $airConditioning);

        return "{$categoryLetter}{$typeLetter}{$transmissionLetter}{$fuelAcLetter}";
    }

    private function resolveCategoryLetter(array $attributes, string $bodyStyle): string
    {
        $category = $this->normalizeText($attributes['category_name'] ?? null);
        $seats = $this->toInt($attributes['seating_capacity'] ?? null) ?? 0;
        $horsepower = $this->toInt($attributes['horsepower'] ?? null) ?? 0;

        if ($category && str_contains($category, 'luxury')) {
            return 'L';
        }

        if ($category && str_contains($category, 'premium')) {
            return 'P';
        }

        if (in_array($bodyStyle, ['van', 'minivan'], true)) {
            return $seats >= 7 ? 'S' : 'I';
        }

        if ($bodyStyle === 'suv') {
            if ($category && str_contains($category, 'luxury')) {
                return 'L';
            }

            if ($horsepower >= 190 || $seats >= 7) {
                return 'F';
            }

            return 'I';
        }

        if ($horsepower >= 220) {
            return 'L';
        }

        if ($horsepower >= 170 || $seats >= 7) {
            return 'F';
        }

        if ($horsepower >= 130 || $seats >= 5) {
            return 'I';
        }

        if ($horsepower >= 90) {
            return 'C';
        }

        return 'E';
    }

    private function resolveTypeLetter(string $bodyStyle): string
    {
        return match ($bodyStyle) {
            'suv' => 'F',
            'wagon', 'estate' => 'W',
            'van', 'minivan' => 'V',
            'convertible', 'cabriolet' => 'N',
            'pickup' => 'P',
            default => 'C',
        };
    }

    private function resolveFuelAcLetter(string $fuel, bool $airConditioning): string
    {
        if (!$airConditioning) {
            return match ($fuel) {
                'diesel' => 'Q',
                'hybrid' => 'I',
                'electric' => 'C',
                default => 'N',
            };
        }

        return match ($fuel) {
            'diesel' => 'Q',
            'hybrid' => 'H',
            'electric' => 'E',
            default => 'R',
        };
    }

    private function normalizeCode(?string $value): ?string
    {
        $value = strtoupper(trim((string) ($value ?? '')));
        $value = preg_replace('/[^A-Z]/', '', $value ?? '');

        return strlen((string) $value) === 4 ? $value : null;
    }

    private function normalizeText(mixed $value): ?string
    {
        $value = strtolower(trim((string) ($value ?? '')));

        return $value === '' ? null : $value;
    }

    private function normalizeBool(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (in_array($value, [1, '1', 'true', 'yes'], true)) {
            return true;
        }

        if (in_array($value, [0, '0', 'false', 'no'], true)) {
            return false;
        }

        return null;
    }

    private function toInt(mixed $value): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }
}
