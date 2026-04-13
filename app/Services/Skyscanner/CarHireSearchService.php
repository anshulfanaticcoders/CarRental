<?php

namespace App\Services\Skyscanner;

use Carbon\CarbonInterface;

class CarHireSearchService
{
    public function __construct(
        private readonly CarHireInternalInventoryService $internalInventoryService,
        private readonly CarHireQuoteMapper $quoteMapper,
        private readonly CarHireQuoteLifecycleService $quoteLifecycleService,
        private readonly CarHireQuoteStoreService $quoteStoreService,
        private readonly CarHireAuditLogService $auditLogService,
    ) {
    }

    public function search(array $criteria): array
    {
        $vehicles = $this->internalInventoryService->search($criteria);

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
}
