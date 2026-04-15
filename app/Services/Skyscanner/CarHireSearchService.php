<?php

namespace App\Services\Skyscanner;

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
    ) {
    }

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
            $mappedVehicle = $this->quoteMapper->map($vehicle);

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

        foreach ($quotes as $quote) {
            $this->quoteStoreService->put($quote, $offerResults);
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
            $key = $source . '|' . $identity;

            if (array_key_exists($key, $deduplicated)) {
                continue;
            }

            $deduplicated[$key] = $vehicle;
        }

        return array_values($deduplicated);
    }
}
