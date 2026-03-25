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
        $groups = [];

        foreach ($vehicles as $vehicle) {
            $normalized = $this->normalizeVehicle($vehicle);
            if (($normalized['source'] ?? null) !== 'renteon') {
                continue;
            }

            $key = $this->renteonCollapseKey($normalized);
            if ($key === null) {
                continue;
            }

            $groups[$key][] = $normalized;
        }

        $preferredByKey = [];
        foreach ($groups as $key => $group) {
            if (count($group) < 2) {
                continue;
            }

            $preferredByKey[$key] = $this->groupRenteonVariants($group);
        }

        if ($preferredByKey === []) {
            return $vehicles->values();
        }

        $emittedKeys = [];

        return $vehicles->flatMap(function ($vehicle) use (&$emittedKeys, $preferredByKey) {
            $normalized = $this->normalizeVehicle($vehicle);
            if (($normalized['source'] ?? null) !== 'renteon') {
                return [$vehicle];
            }

            $key = $this->renteonCollapseKey($normalized);
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

        if ($providerCode === '' || $currency === '') {
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
            mb_strtolower(trim((string) ($vehicle['category'] ?? ''))),
            mb_strtolower(trim((string) ($vehicle['transmission'] ?? ''))),
            mb_strtolower(trim((string) ($vehicle['fuel'] ?? ''))),
            trim((string) ($vehicle['seating_capacity'] ?? '')),
            trim((string) ($vehicle['doors'] ?? '')),
            trim((string) ($vehicle['luggageSmall'] ?? '')),
            trim((string) ($vehicle['luggageLarge'] ?? '')),
            !empty($vehicle['airConditioning']) ? '1' : '0',
            $currency,
            !empty($vehicle['prepaid']) ? '1' : '0',
            !empty($vehicle['is_on_request']) ? '1' : '0',
            $this->renteonExtrasSignature($vehicle['extras'] ?? []),
        ]);
    }

    private function groupRenteonVariants(array $group): array
    {
        usort($group, function (array $left, array $right): int {
            $leftTotal = round((float) ($left['total_price'] ?? 0), 2);
            $rightTotal = round((float) ($right['total_price'] ?? 0), 2);
            if ($leftTotal !== $rightTotal) {
                return $leftTotal <=> $rightTotal;
            }

            $leftDeposit = (float) ($left['benefits']['deposit_amount'] ?? $left['security_deposit'] ?? INF);
            $rightDeposit = (float) ($right['benefits']['deposit_amount'] ?? $right['security_deposit'] ?? INF);
            if ($leftDeposit !== $rightDeposit) {
                return $leftDeposit <=> $rightDeposit;
            }

            $leftExcess = (float) ($left['benefits']['excess_amount'] ?? INF);
            $rightExcess = (float) ($right['benefits']['excess_amount'] ?? INF);

            return $leftExcess <=> $rightExcess;
        });

        $uniqueVariants = collect($group)->reduce(function (array $carry, array $vehicle) {
            $signature = $this->renteonVariantSignature($vehicle);
            if (!isset($carry[$signature])) {
                $carry[$signature] = $vehicle;
            }

            return $carry;
        }, []);

        $variants = array_values($uniqueVariants);
        $baseVehicle = $variants[0];
        $baseVehicle['products'] = array_map(
            fn (array $variant, int $index) => $this->buildRenteonVariantProduct($variant, $index),
            $variants,
            array_keys($variants)
        );
        $baseVehicle['rate_variants_count'] = count($baseVehicle['products']);

        return $baseVehicle;
    }

    private function buildRenteonVariantProduct(array $variant, int $index): array
    {
        $sourceProduct = collect($variant['products'] ?? [])->first() ?: [];
        $currency = strtoupper(trim((string) ($sourceProduct['currency'] ?? $variant['currency'] ?? 'EUR')));
        $depositAmount = $variant['benefits']['deposit_amount']
            ?? $variant['security_deposit']
            ?? ($sourceProduct['deposit'] ?? null);
        $depositCurrency = $variant['benefits']['deposit_currency']
            ?? ($sourceProduct['deposit_currency'] ?? $currency);
        $excessAmount = $variant['benefits']['excess_amount']
            ?? ($sourceProduct['excess'] ?? null);
        $excessTheftAmount = $variant['benefits']['excess_theft_amount']
            ?? ($sourceProduct['excess_theft_amount'] ?? null);
        $type = $variant['pricelist_id']
            ? 'REN_' . trim((string) $variant['pricelist_id'])
            : 'REN_' . ($index + 1);

        return array_merge($sourceProduct, [
            'type' => $type,
            'name' => 'Rate Option ' . ($index + 1),
            'subtitle' => $this->buildRenteonVariantSubtitle($depositAmount, $excessAmount, $depositCurrency),
            'total' => round((float) ($sourceProduct['total'] ?? $variant['total_price'] ?? 0), 2),
            'price_per_day' => round((float) ($sourceProduct['price_per_day'] ?? $variant['price_per_day'] ?? 0), 2),
            'currency' => $currency,
            'deposit' => $depositAmount,
            'deposit_currency' => $depositCurrency,
            'excess' => $excessAmount,
            'excess_theft_amount' => $excessTheftAmount,
            'gateway_vehicle_id' => $variant['gateway_vehicle_id'] ?? ($variant['id'] ?? null),
            'provider_vehicle_id' => $variant['provider_vehicle_id'] ?? null,
            'connector_id' => $variant['connector_id'] ?? null,
            'provider_pickup_office_id' => $variant['provider_pickup_office_id'] ?? null,
            'provider_dropoff_office_id' => $variant['provider_dropoff_office_id'] ?? null,
            'pricelist_id' => $variant['pricelist_id'] ?? null,
            'pricelist_code' => $variant['pricelist_code'] ?? null,
            'price_date' => $variant['price_date'] ?? null,
            'prepaid' => $variant['prepaid'] ?? false,
            'is_on_request' => $variant['is_on_request'] ?? false,
            'provider_net_amount' => $variant['provider_net_amount'] ?? null,
            'provider_vat_amount' => $variant['provider_vat_amount'] ?? null,
            'provider_gross_amount' => $variant['provider_gross_amount'] ?? null,
            'benefits' => array_values(array_filter([
                $depositAmount !== null ? 'Deposit: ' . $depositCurrency . ' ' . number_format((float) $depositAmount, 2, '.', '') : null,
                $excessAmount !== null ? 'Excess: ' . $depositCurrency . ' ' . number_format((float) $excessAmount, 2, '.', '') : null,
                $excessTheftAmount !== null ? 'Theft Excess: ' . $depositCurrency . ' ' . number_format((float) $excessTheftAmount, 2, '.', '') : null,
            ])),
            'isBestValue' => $index === 0,
        ]);
    }

    private function buildRenteonVariantSubtitle(mixed $depositAmount, mixed $excessAmount, string $currency): string
    {
        $parts = [];

        if ($depositAmount !== null && $depositAmount !== '') {
            $parts[] = 'Deposit ' . $currency . ' ' . number_format((float) $depositAmount, 2, '.', '');
        }

        if ($excessAmount !== null && $excessAmount !== '') {
            $parts[] = 'Excess ' . $currency . ' ' . number_format((float) $excessAmount, 2, '.', '');
        }

        return implode(' • ', $parts);
    }

    private function renteonVariantSignature(array $vehicle): string
    {
        $currency = strtoupper(trim((string) ($vehicle['currency'] ?? 'EUR')));
        $depositAmount = $vehicle['benefits']['deposit_amount'] ?? $vehicle['security_deposit'] ?? null;
        $depositCurrency = $vehicle['benefits']['deposit_currency'] ?? $currency;
        $excessAmount = $vehicle['benefits']['excess_amount'] ?? null;
        $excessTheftAmount = $vehicle['benefits']['excess_theft_amount'] ?? null;

        return implode('|', [
            number_format((float) ($vehicle['total_price'] ?? 0), 2, '.', ''),
            number_format((float) ($vehicle['price_per_day'] ?? 0), 2, '.', ''),
            $depositAmount === null ? '' : number_format((float) $depositAmount, 2, '.', ''),
            $depositCurrency,
            $excessAmount === null ? '' : number_format((float) $excessAmount, 2, '.', ''),
            $excessTheftAmount === null ? '' : number_format((float) $excessTheftAmount, 2, '.', ''),
            !empty($vehicle['prepaid']) ? '1' : '0',
            !empty($vehicle['is_on_request']) ? '1' : '0',
        ]);
    }

    private function renteonExtrasSignature(array $extras): string
    {
        return collect($extras)
            ->map(function (mixed $extra): string {
                $item = is_array($extra) ? $extra : (array) $extra;

                return implode('|', [
                    trim((string) ($item['code'] ?? $item['id'] ?? '')),
                    trim((string) ($item['name'] ?? $item['description'] ?? '')),
                    number_format((float) ($item['daily_rate'] ?? $item['price'] ?? 0), 2, '.', ''),
                    number_format((float) ($item['total_price'] ?? $item['total_for_booking'] ?? 0), 2, '.', ''),
                    !empty($item['included']) ? '1' : '0',
                    !empty($item['required']) ? '1' : '0',
                ]);
            })
            ->sort()
            ->implode('||');
    }

    private function normalizeVehicle(mixed $vehicle): array
    {
        if (is_array($vehicle)) {
            return $vehicle;
        }

        return (array) $vehicle;
    }
}
