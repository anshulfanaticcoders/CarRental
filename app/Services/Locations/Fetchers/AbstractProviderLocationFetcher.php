<?php

namespace App\Services\Locations\Fetchers;

use App\Services\Locations\ProviderLocationFetcherInterface;

abstract class AbstractProviderLocationFetcher implements ProviderLocationFetcherInterface
{
    protected function normalizeTitleCase(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $value = str_replace(['_', '-'], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return ucwords(strtolower((string) $value));
    }

    protected function normalizeCountryName(?string $country): ?string
    {
        $value = trim((string) $country);
        if ($value === '') {
            return null;
        }

        $normalized = $value;
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
        if ($transliterated !== false && $transliterated !== '') {
            $normalized = $transliterated;
        }

        $key = strtoupper((string) preg_replace('/[^A-Z]/', '', $normalized));
        $map = [
            'TR' => 'Turkiye',
            'TURKEY' => 'Turkiye',
            'TURKIYE' => 'Turkiye',
        ];

        if (isset($map[$key])) {
            return $map[$key];
        }

        return $this->normalizeTitleCase($value);
    }

    protected function getCountryName(?string $countryCode): ?string
    {
        $countryCode = strtoupper(trim((string) $countryCode));

        return $this->getCountryFallbackMap()[$countryCode] ?? ($countryCode !== '' ? $countryCode : null);
    }

    protected function getCountryFallbackMap(): array
    {
        return [
            'AE' => 'United Arab Emirates',
            'US' => 'United States',
            'GB' => 'United Kingdom',
            'ES' => 'Spain',
            'IT' => 'Italy',
            'FR' => 'France',
            'DE' => 'Germany',
            'GR' => 'Greece',
            'MA' => 'Morocco',
            'TR' => 'Turkiye',
            'PT' => 'Portugal',
            'IC' => 'Canary Islands',
        ];
    }
}
