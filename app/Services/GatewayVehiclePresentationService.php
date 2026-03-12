<?php

namespace App\Services;

use Illuminate\Support\Collection;

class GatewayVehiclePresentationService
{
    public function collapseEquivalentSicilyByCarVehicles(Collection $vehicles): Collection
    {
        $groups = [];

        foreach ($vehicles as $vehicle) {
            $normalized = $this->normalizeVehicle($vehicle);
            if (($normalized['source'] ?? null) !== 'sicily_by_car') {
                continue;
            }

            $key = $this->collapseKey($normalized);
            if ($key === null) {
                continue;
            }

            $groups[$key][] = $normalized;
        }

        $preferredByKey = [];
        foreach ($groups as $key => $group) {
            if (!$this->shouldCollapseGroup($group)) {
                continue;
            }

            $preferredByKey[$key] = $this->selectPreferredVehicle($group);
        }

        if ($preferredByKey === []) {
            return $vehicles->values();
        }

        $emittedKeys = [];

        return $vehicles->flatMap(function ($vehicle) use (&$emittedKeys, $preferredByKey) {
            $normalized = $this->normalizeVehicle($vehicle);
            if (($normalized['source'] ?? null) !== 'sicily_by_car') {
                return [$vehicle];
            }

            $key = $this->collapseKey($normalized);
            if ($key === null || !isset($preferredByKey[$key])) {
                return [$vehicle];
            }

            if (isset($emittedKeys[$key])) {
                return [];
            }

            $emittedKeys[$key] = true;

            return [$preferredByKey[$key]];
        })->values();
    }

    public function collapseEquivalentRenteonVehicles(Collection $vehicles): Collection
    {
        $seen = [];

        return $vehicles->filter(function ($vehicle) use (&$seen) {
            $normalized = $this->normalizeVehicle($vehicle);
            if (($normalized['source'] ?? null) !== 'renteon') {
                return true;
            }

            $key = $this->renteonCollapseKey($normalized);
            if ($key === null) {
                return true;
            }

            if (isset($seen[$key])) {
                return false;
            }

            $seen[$key] = true;

            return true;
        })->values();
    }

    private function shouldCollapseGroup(array $group): bool
    {
        if (count($group) !== 2) {
            return false;
        }

        $payments = collect($group)
            ->map(fn(array $vehicle) => $this->normalizePaymentType($vehicle['payment_type'] ?? null, $vehicle['rate_id'] ?? null))
            ->filter()
            ->unique()
            ->values();

        return $payments->count() === 2
            && $payments->contains('pay_on_arrival')
            && $payments->contains('prepaid');
    }

    private function selectPreferredVehicle(array $group): array
    {
        foreach ($group as $vehicle) {
            if ($this->normalizePaymentType($vehicle['payment_type'] ?? null, $vehicle['rate_id'] ?? null) === 'pay_on_arrival') {
                return $vehicle;
            }
        }

        return $group[0];
    }

    private function collapseKey(array $vehicle): ?string
    {
        $providerVehicleId = trim((string) ($vehicle['provider_vehicle_id'] ?? ''));
        $currency = strtoupper(trim((string) ($vehicle['currency'] ?? '')));
        $total = round((float) ($vehicle['total_price'] ?? 0), 2);

        if ($providerVehicleId === '' || $currency === '' || $total <= 0) {
            return null;
        }

        return implode('|', [
            'sicily_by_car',
            trim((string) ($vehicle['provider_pickup_id'] ?? '')),
            trim((string) ($vehicle['provider_return_id'] ?? '')),
            $providerVehicleId,
            mb_strtolower(trim((string) ($vehicle['model'] ?? ''))),
            mb_strtolower(trim((string) ($vehicle['sipp_code'] ?? ''))),
            $currency,
            number_format($total, 2, '.', ''),
        ]);
    }

    private function normalizePaymentType(mixed $paymentType, mixed $rateId): ?string
    {
        $payment = strtoupper(trim((string) ($paymentType ?? '')));
        $rate = strtoupper(trim((string) ($rateId ?? '')));

        if ($payment === 'PAYONARRIVAL' || str_ends_with($rate, '-POA')) {
            return 'pay_on_arrival';
        }

        if ($payment === 'PREPAID' || str_ends_with($rate, '-PP') || str_ends_with($rate, '-PRE')) {
            return 'prepaid';
        }

        return null;
    }

    private function renteonCollapseKey(array $vehicle): ?string
    {
        $providerCode = trim((string) ($vehicle['provider_code'] ?? ''));
        $currency = strtoupper(trim((string) ($vehicle['currency'] ?? '')));
        $total = round((float) ($vehicle['total_price'] ?? 0), 2);

        if ($providerCode === '' || $currency === '' || $total <= 0) {
            return null;
        }

        return implode('|', [
            'renteon',
            $providerCode,
            trim((string) ($vehicle['provider_pickup_id'] ?? '')),
            trim((string) ($vehicle['provider_return_id'] ?? '')),
            trim((string) ($vehicle['provider_pickup_office_id'] ?? '')),
            trim((string) ($vehicle['provider_dropoff_office_id'] ?? '')),
            trim((string) ($vehicle['connector_id'] ?? '')),
            mb_strtolower(trim((string) ($vehicle['provider_vehicle_id'] ?? ''))),
            mb_strtolower(trim((string) ($vehicle['brand'] ?? ''))),
            mb_strtolower(trim((string) ($vehicle['model'] ?? ''))),
            mb_strtolower(trim((string) ($vehicle['sipp_code'] ?? ''))),
            $currency,
            number_format($total, 2, '.', ''),
            !empty($vehicle['prepaid']) ? '1' : '0',
            !empty($vehicle['is_on_request']) ? '1' : '0',
        ]);
    }

    private function normalizeVehicle(mixed $vehicle): array
    {
        if (is_array($vehicle)) {
            return $vehicle;
        }

        return (array) $vehicle;
    }
}
