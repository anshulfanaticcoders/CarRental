<?php

namespace App\Services\Skyscanner;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class CarHireSearchService
{
    public function __construct(
        private readonly CarHireInternalInventoryService $internalInventoryService,
        private readonly CarHireGatewayInventoryService $gatewayInventoryService,
        private readonly CarHireQuoteMapper $quoteMapper,
        private readonly CarHireQuoteLifecycleService $quoteLifecycleService,
        private readonly CarHireQuoteStoreService $quoteStoreService,
        private readonly CarHireAuditLogService $auditLogService,
    ) {}

    public function search(array $criteria): array
    {
        $scope = (string) config('skyscanner.inventory_scope', 'internal');
        $vehicles = [];

        if (in_array($scope, ['internal', 'mixed'], true)) {
            $vehicles = array_merge($vehicles, $this->internalInventoryService->search($criteria));
        }

        if (in_array($scope, ['providers', 'mixed'], true)) {
            $vehicles = array_merge($vehicles, $this->gatewayInventoryService->search($criteria));
        }

        $vehicles = $this->deduplicateVehicles($vehicles);

        return $this->buildQuotes($vehicles, $criteria);
    }

    public function buildQuotes(array $vehicles, array $search, ?CarbonInterface $now = null): array
    {
        $quotes = [];
        $excludedVehicleIds = [];

        foreach ($vehicles as $vehicle) {
            $mappedVehicle = $this->applyCustomerPricing($this->quoteMapper->map($vehicle), $search);

            if (($mappedVehicle['validation']['ready'] ?? false) !== true) {
                $providerVehicleId = (string) ($mappedVehicle['provider_vehicle_id'] ?? '');

                if ($providerVehicleId !== '') {
                    $excludedVehicleIds[] = $providerVehicleId;
                    $this->auditLogService->append('candidate', $providerVehicleId, 'quote_rejected', [
                        'validation_errors' => $mappedVehicle['validation']['errors'] ?? [],
                    ]);
                }

                continue;
            }

            $quote = $this->quoteLifecycleService->createQuote($mappedVehicle, $search, $now);
            $quotes[] = $quote;
        }

        $offerResults = [
            'search' => $search,
            'quotes' => $quotes,
        ];

        // One shared write for the whole search; each quote stores a pointer.
        $sharedKey = null;
        if ($quotes !== []) {
            $sharedKey = (string) \Illuminate\Support\Str::uuid();
            $latestExpiry = \Carbon\CarbonImmutable::parse(
                (string) (collect($quotes)->max('expires_at') ?? now('UTC')->toIso8601String())
            );
            $this->quoteStoreService->putSharedOfferResults($sharedKey, $offerResults, $latestExpiry);
        }

        foreach ($quotes as $quote) {
            $this->quoteStoreService->put($quote, null, $sharedKey);
            $this->auditLogService->append('quote', (string) $quote['quote_id'], 'quote_created', [
                'provider_vehicle_id' => $quote['vehicle']['provider_vehicle_id'] ?? null,
                'currency' => $quote['pricing']['currency'] ?? null,
                'total_price' => $quote['pricing']['total_price'] ?? null,
            ]);
        }

        return [
            'search' => $search,
            'quotes' => $quotes,
            'excluded_vehicle_ids' => array_values(array_unique($excludedVehicleIds)),
        ];
    }

    private function deduplicateVehicles(array $vehicles): array
    {
        $deduplicated = [];

        foreach ($vehicles as $vehicle) {
            $source = strtolower(trim((string) ($vehicle['source'] ?? $vehicle['provider_code'] ?? 'unknown')));
            $providerVehicleId = trim((string) ($vehicle['provider_vehicle_id'] ?? $vehicle['id'] ?? ''));
            $identity = $providerVehicleId !== '' ? $providerVehicleId : md5(json_encode($vehicle));
            $key = $source.'|'.$identity;

            if (array_key_exists($key, $deduplicated)) {
                continue;
            }

            $deduplicated[$key] = $vehicle;
        }

        return array_values($deduplicated);
    }

    private function applyCustomerPricing(array $vehicle, array $search): array
    {
        $pricing = is_array($vehicle['pricing'] ?? null) ? $vehicle['pricing'] : [];
        $total = $this->floatOrNull($pricing['total_price'] ?? $vehicle['total_price'] ?? null);

        if ($total === null || $total <= 0) {
            return $vehicle;
        }

        $days = $this->rentalDays($search);

        // FX first: quotes go out in the currency Skyscanner asked for via a
        // REAL conversion — the requested code used to be stamped onto raw
        // EUR amounts (a ~8% price-accuracy lie in USD markets). If no rate
        // is available the quote keeps its true source currency instead.
        $sourceCurrency = strtoupper(trim((string) ($pricing['currency'] ?? $vehicle['currency'] ?? 'EUR'))) ?: 'EUR';
        $requestedCurrency = strtoupper(trim((string) ($search['currency'] ?? '')));
        $fxRate = 1.0;
        $quoteCurrency = $sourceCurrency;
        if ($requestedCurrency !== '' && $requestedCurrency !== $sourceCurrency) {
            $conversion = app(\App\Services\CurrencyConversionService::class)->convert(1.0, $sourceCurrency, $requestedCurrency);
            if ($conversion['success'] ?? false) {
                $fxRate = (float) ($conversion['converted_amount'] ?? 1.0);
                $quoteCurrency = $requestedCurrency;
            }
        }
        $customerTotal = round($total * $fxRate, 2);

        // The CHECKOUT markup rule, not the deposit percentage: external
        // inventory carries the platform markup, internal fleet sells at net —
        // the quote must match what the landing page will actually charge.
        $rate = $this->isProviderVehicle($vehicle)
            ? app(\App\Services\Pricing\CustomerMarkupService::class)->rate()
            : 0.0;
        $grossTotal = round($customerTotal * (1 + max(0, $rate)), 2);
        $grossPricePerDay = round($grossTotal / $days, 2);

        $vehicle['net_pricing'] = $pricing;
        $vehicle['booking_products'] = is_array($vehicle['products'] ?? null) ? $vehicle['products'] : [];
        $vehicle['pricing'] = array_merge($pricing, [
            'total_price' => $grossTotal,
            'price_per_day' => $grossPricePerDay,
            'currency' => $quoteCurrency,
            'customer_price_markup_rate' => $rate,
            'source_currency' => $sourceCurrency,
            'fx_rate' => $fxRate,
        ]);

        return $vehicle;
    }

    private function isProviderVehicle(array $vehicle): bool
    {
        $source = strtolower(trim((string) ($vehicle['source'] ?? data_get($vehicle, 'supplier.code') ?? $vehicle['provider_code'] ?? '')));

        return $source !== '' && $source !== 'internal';
    }

    private function rentalDays(array $search): int
    {
        $pickupDate = (string) ($search['pickup_date'] ?? '');
        $dropoffDate = (string) ($search['dropoff_date'] ?? '');

        if ($pickupDate === '' || $dropoffDate === '') {
            return 1;
        }

        try {
            return max(1, (int) abs(CarbonImmutable::parse($pickupDate)->diffInDays(CarbonImmutable::parse($dropoffDate))));
        } catch (\Throwable) {
            return 1;
        }
    }

    private function floatOrNull(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
