<?php

namespace App\Services\Skyscanner;

class CarHireQuoteMapper
{
    public function __construct(
        private readonly CarHireFieldStrategyService $fieldStrategyService,
        private readonly CarHireQuoteValidationService $quoteValidationService,
    ) {
    }

    public function map(array $quote): array
    {
        $mapped = $quote;
        $mapped['supplier'] = $this->resolveSupplier($quote);
        $mapped['specs'] = $this->resolveSpecs($quote);
        $mapped['location'] = $this->resolveLocations($quote);
        $mapped['data_quality_flags'] = $this->resolveDataQualityFlags($quote, $mapped);
        $mapped['validation'] = $this->quoteValidationService->validate($mapped);

        return $mapped;
    }

    private function resolveSupplier(array $quote): array
    {
        $supplier = $this->fieldStrategyService->resolveSupplier($quote);

        return [
            'code' => $supplier['code'],
            'name' => $supplier['name'],
        ];
    }

    private function resolveSpecs(array $quote): array
    {
        $specs = is_array($quote['specs'] ?? null) ? $quote['specs'] : [];
        $sipp = $this->fieldStrategyService->resolveSipp($quote);
        $specs['sipp_code'] = $sipp['code'];
        $specs['sipp_source'] = $sipp['source'];

        return $specs;
    }

    private function resolveLocations(array $quote): array
    {
        $location = is_array($quote['location'] ?? null) ? $quote['location'] : [];
        $pickup = is_array($location['pickup'] ?? null) ? $location['pickup'] : [];
        $dropoff = is_array($location['dropoff'] ?? null) ? $location['dropoff'] : [];

        $pickupId = $this->fieldStrategyService->resolveLocationIdentifier($pickup);
        $dropoffId = $this->fieldStrategyService->resolveLocationIdentifier($dropoff);

        $pickup['provider_location_id'] = $pickupId['id'];
        $pickup['provider_location_source'] = $pickupId['source'];
        $dropoff['provider_location_id'] = $dropoffId['id'];
        $dropoff['provider_location_source'] = $dropoffId['source'];

        return [
            'pickup' => $pickup,
            'dropoff' => $dropoff,
        ];
    }

    private function resolveDataQualityFlags(array $quote, array $mapped): array
    {
        $flags = [];

        if ($this->nullableString($mapped['specs']['sipp_code'] ?? null) === null) {
            $flags[] = 'missing_sipp_code';
        }

        $pickupId = $this->nullableString(data_get($mapped, 'location.pickup.provider_location_id'));
        if ($pickupId === null) {
            $flags[] = 'missing_pickup_location_id';
        }

        $dropoffId = $this->nullableString(data_get($mapped, 'location.dropoff.provider_location_id'));
        if ($dropoffId === null) {
            $flags[] = 'missing_dropoff_location_id';
        }

        return array_values(array_unique($flags));
    }

    private function nullableString(mixed $value): ?string
    {
        $string = trim((string) ($value ?? ''));

        return $string === '' ? null : $string;
    }
}
