<?php

namespace App\Services\Skyscanner;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class CarHireQuoteLifecycleService
{
    public function createQuote(array $vehicle, array $search, ?CarbonInterface $now = null): array
    {
        $createdAt = $this->normalizeNow($now);
        $expiresAt = $createdAt->addMinutes((int) config('skyscanner.quote_ttl_minutes', 30));

        return [
            'quote_id' => (string) Str::uuid(),
            'case_id' => (string) config('skyscanner.case_id'),
            'created_at' => $createdAt->toIso8601String(),
            'expires_at' => $expiresAt->toIso8601String(),
            'vehicle' => [
                'provider_vehicle_id' => $vehicle['provider_vehicle_id'] ?? null,
                'display_name' => $vehicle['display_name'] ?? null,
            ],
            'pricing' => $vehicle['pricing'] ?? [],
            'policies' => $vehicle['policies'] ?? [],
            'search' => $search,
        ];
    }

    public function revalidate(array $quote, ?CarbonInterface $now = null): array
    {
        $expiresAt = CarbonImmutable::parse((string) ($quote['expires_at'] ?? ''));
        $currentTime = $this->normalizeNow($now);

        if ($currentTime->greaterThan($expiresAt)) {
            return [
                'valid' => false,
                'reason' => 'expired',
            ];
        }

        return [
            'valid' => true,
            'reason' => null,
        ];
    }

    public function detectMismatch(array $storedQuote, array $currentQuote): array
    {
        $storedVehicleId = $storedQuote['vehicle']['provider_vehicle_id'] ?? null;
        $currentVehicleId = $currentQuote['vehicle']['provider_vehicle_id'] ?? null;

        if ($storedVehicleId !== $currentVehicleId) {
            return [
                'mismatched' => true,
                'reason' => 'vehicle_changed',
            ];
        }

        $storedCurrency = $storedQuote['pricing']['currency'] ?? null;
        $currentCurrency = $currentQuote['pricing']['currency'] ?? null;

        if ($storedCurrency !== $currentCurrency) {
            return [
                'mismatched' => true,
                'reason' => 'currency_changed',
            ];
        }

        $storedPrice = $storedQuote['pricing']['total_price'] ?? null;
        $currentPrice = $currentQuote['pricing']['total_price'] ?? null;

        if ((float) $storedPrice !== (float) $currentPrice) {
            return [
                'mismatched' => true,
                'reason' => 'price_changed',
            ];
        }

        return [
            'mismatched' => false,
            'reason' => null,
        ];
    }

    private function normalizeNow(?CarbonInterface $now): CarbonImmutable
    {
        if ($now === null) {
            return CarbonImmutable::now('UTC');
        }

        return CarbonImmutable::instance($now);
    }
}
