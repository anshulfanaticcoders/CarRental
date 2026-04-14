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
        $typeLetter = $this->resolveTypeLetter($bodyStyle, $attributes);
        $transmissionLetter = $transmission === 'automatic' ? 'A' : 'M';
        $fuelAcLetter = $this->resolveFuelAcLetter($fuel, $airConditioning);

        return "{$categoryLetter}{$typeLetter}{$transmissionLetter}{$fuelAcLetter}";
    }

    private function resolveCategoryLetter(array $attributes, string $bodyStyle): string
    {
        $category = $this->normalizeText($attributes['category_name'] ?? null);
        $brand = $this->normalizeText($attributes['brand'] ?? null);
        $model = $this->normalizeText($attributes['model'] ?? null);
        $seats = $this->toInt($attributes['seating_capacity'] ?? null) ?? 0;
        $horsepower = $this->toInt($attributes['horsepower'] ?? null) ?? 0;
        $lookup = implode(' ', array_filter([$category, $brand, $model]));

        if ($this->isSpecialPerformanceProfile($lookup, $bodyStyle, $horsepower)) {
            return 'X';
        }

        if ($category && $this->containsAny($category, ['luxury', 'prestige'])) {
            return 'L';
        }

        if ($category && $this->containsAny($category, ['premium'])) {
            return 'P';
        }

        if (in_array($bodyStyle, ['van', 'minivan'], true)) {
            return $seats >= 7 ? 'S' : 'I';
        }

        if ($bodyStyle === 'suv') {
            return match (true) {
                $category && $this->containsAny($category, ['luxury', 'prestige']) => 'L',
                $category && $this->containsAny($category, ['premium']) => 'P',
                $horsepower >= 190 || $seats >= 7 => 'F',
                default => 'I',
            };
        }

        if ($category && $this->containsAny($category, ['mini'])) {
            return 'M';
        }

        if ($category && $this->containsAny($category, ['city', 'economy'])) {
            return 'E';
        }

        if ($category && $this->containsAny($category, ['compact'])) {
            return 'C';
        }

        if ($category && $this->containsAny($category, ['intermediate', 'mid size', 'midsize'])) {
            return 'I';
        }

        if ($category && $this->containsAny($category, ['standard'])) {
            return 'S';
        }

        if ($category && $this->containsAny($category, ['full size', 'fullsize'])) {
            return 'F';
        }

        if ($horsepower >= 220) {
            return 'P';
        }

        if ($horsepower >= 170 || $seats >= 7) {
            return 'F';
        }

        if ($horsepower >= 130) {
            return 'I';
        }

        if ($horsepower >= 90) {
            return 'C';
        }

        return 'E';
    }

    private function resolveTypeLetter(string $bodyStyle, array $attributes): string
    {
        $doors = $this->toInt($attributes['number_of_doors'] ?? null) ?? 4;

        return match ($bodyStyle) {
            'sport' => 'S',
            'roadster' => 'N',
            'convertible', 'cabriolet' => 'T',
            'coupe' => 'E',
            'suv' => 'F',
            'wagon', 'estate' => 'W',
            'van', 'minivan' => 'V',
            'pickup' => 'P',
            default => $doors >= 4 ? 'D' : 'C',
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
            'diesel' => 'D',
            'hybrid' => 'H',
            'electric' => 'E',
            default => 'R',
        };
    }

    private function isSpecialPerformanceProfile(string $lookup, string $bodyStyle, int $horsepower): bool
    {
        if (in_array($bodyStyle, ['sport', 'roadster'], true)) {
            return true;
        }

        if ($this->containsAny($lookup, [
            'sports car',
            'sport car',
            'performance',
            'supercar',
            'exotic',
            'corvette',
            'huracan',
            'lamborghini',
            'ferrari',
            'mclaren',
            '911',
            'porsche 911',
            'aston martin',
            'amg gt',
            'r8',
            'nissan gt r',
            'gtr',
        ])) {
            return in_array($bodyStyle, ['convertible', 'coupe'], true) || $horsepower >= 220;
        }

        return false;
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

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
