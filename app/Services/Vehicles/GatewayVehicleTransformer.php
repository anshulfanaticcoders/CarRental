<?php

namespace App\Services\Vehicles;

class GatewayVehicleTransformer
{
    public function transform(array $gv, int $rentalDays): array
    {
        $rawSupplierId = $gv['supplier_id'] ?? 'unknown';
        $supplierId = $this->normalizeSupplierId((string) $rawSupplierId);
        $supplierVehicleId = $gv['supplier_vehicle_id'] ?? '';
        $pricing = $gv['pricing'] ?? [];
        $pickup = $gv['pickup_location'] ?? [];
        $supplierData = $gv['supplier_data'] ?? [];
        $totalPrice = (float) ($pricing['total_price'] ?? 0);
        $dailyRate = (float) ($pricing['daily_rate'] ?? 0);
        $priceCurrency = $pricing['currency'] ?? 'EUR';

        $transmission = $gv['transmission'] ?? 'manual';

        $fuelMap = [
            'petrol' => 'Petrol',
            'diesel' => 'Diesel',
            'electric' => 'Electric',
            'hybrid' => 'Hybrid',
            'lpg' => 'LPG',
            'unknown' => null,
        ];
        $fuel = $fuelMap[$gv['fuel_type'] ?? 'unknown'] ?? ($gv['fuel_type'] ?? null);

        $features = [];
        if ($gv['air_conditioning'] ?? true) {
            $features[] = 'Air Conditioning';
        }

        $extras = collect($gv['extras'] ?? [])->map(function ($extra) {
            $dailyRate = (float) ($extra['daily_rate'] ?? 0);
            $totalPrice = (float) ($extra['total_price'] ?? 0);
            $extCurrency = $extra['currency'] ?? 'EUR';
            $maxQty = $extra['max_quantity'] ?? 1;
            $name = $extra['name'] ?? '';
            $description = $extra['description'] ?? $name;
            $extId = $extra['id'] ?? '';

            return [
                'id' => $extId,
                'name' => $name,
                'description' => $description,
                'daily_rate' => $dailyRate,
                'Daily_rate' => $dailyRate,
                'total_for_booking' => $totalPrice,
                'Total_for_this_booking' => $totalPrice,
                'total_for_booking_currency' => $extCurrency,
                'Total_for_this_booking_currency' => $extCurrency,
                'option_id' => $extId,
                'optionID' => $extId,
                'price' => $dailyRate,
                'amount' => $dailyRate,
                'total_price' => $totalPrice,
                'price_per_day' => $dailyRate,
                'service_id' => $extId,
                'currency' => $extCurrency,
                'max_quantity' => $maxQty,
                'numberAllowed' => $maxQty,
                'mandatory' => $extra['mandatory'] ?? false,
                'required' => $extra['mandatory'] ?? false,
                'type' => $extra['type'] ?? 'equipment',
                'code' => $extId,
            ];
        })->all();
        $options = $extras;

        $insuranceOptions = collect($gv['insurance_options'] ?? [])->map(function ($ins) {
            return [
                'id' => $ins['id'] ?? '',
                'name' => $ins['name'] ?? '',
                'coverage_type' => $ins['coverage_type'] ?? 'basic',
                'daily_rate' => $ins['daily_rate'] ?? 0,
                'total_price' => $ins['total_price'] ?? 0,
                'currency' => $ins['currency'] ?? 'EUR',
                'excess_amount' => $ins['excess_amount'] ?? null,
                'included' => $ins['included'] ?? false,
            ];
        })->all();

        $sbcExtras = $extras;
        if ($rawSupplierId === 'sicily_by_car') {
            $sbcExtras = collect($gv['extras'] ?? [])->map(function ($extra) {
                $sd = $extra['supplier_data'] ?? [];
                return [
                    'id' => $sd['id'] ?? ($extra['id'] ?? ''),
                    'description' => $sd['description'] ?? ($extra['name'] ?? ''),
                    'isMandatory' => $sd['isMandatory'] ?? ($extra['mandatory'] ?? false),
                    'total' => $sd['total'] ?? ($extra['total_price'] ?? 0),
                    'excess' => $sd['excess'] ?? null,
                    'excessAmount' => $sd['excessAmount'] ?? null,
                    'payment' => $sd['payment'] ?? null,
                    'price' => (float) ($extra['daily_rate'] ?? 0),
                    'daily_rate' => (float) ($extra['daily_rate'] ?? 0),
                    'total_price' => (float) ($extra['total_price'] ?? 0),
                    'total_for_booking' => (float) ($extra['total_price'] ?? 0),
                    'name' => $sd['description'] ?? ($extra['name'] ?? ''),
                    'mandatory' => $sd['isMandatory'] ?? ($extra['mandatory'] ?? false),
                ];
            })->all();
        }

        $okMobilityExtras = $extras;
        if ($rawSupplierId === 'ok_mobility') {
            $okMobilityExtras = collect($gv['extras'] ?? [])->map(function ($extra) {
                $sd = $extra['supplier_data'] ?? [];
                return [
                    'extraID' => $sd['extraID'] ?? ($extra['id'] ?? ''),
                    'code' => $sd['code'] ?? ($sd['extraID'] ?? ($extra['id'] ?? '')),
                    'extra' => $sd['extra'] ?? ($extra['name'] ?? ''),
                    'name' => $extra['name'] ?? '',
                    'description' => $extra['description'] ?? '',
                    'value' => $sd['value'] ?? (string) ($extra['daily_rate'] ?? 0),
                    'valueWithTax' => $sd['valueWithTax'] ?? (string) ($extra['daily_rate'] ?? 0),
                    'pricePerContract' => $sd['pricePerContract'] ?? 'false',
                    'extra_Included' => $sd['extra_Included'] ?? 'false',
                    'extra_Required' => $sd['extra_Required'] ?? 'false',
                    'price' => (float) ($extra['daily_rate'] ?? 0),
                    'daily_rate' => (float) ($extra['daily_rate'] ?? 0),
                    'total_price' => (float) ($extra['total_price'] ?? 0),
                    'amount' => (float) ($extra['daily_rate'] ?? 0),
                    'mandatory' => $extra['mandatory'] ?? false,
                    'required' => $extra['mandatory'] ?? false,
                    'type' => $extra['type'] ?? 'equipment',
                    'id' => $extra['id'] ?? '',
                ];
            })->all();
        }

        $renteonExtras = $extras;
        if ($rawSupplierId === 'renteon') {
            $renteonExtras = collect($gv['extras'] ?? [])->map(function ($extra) {
                $sd = $extra['supplier_data'] ?? [];
                $code = $sd['code'] ?? ($extra['id'] ?? '');
                return [
                    'id' => $extra['id'] ?? '',
                    'name' => $extra['name'] ?? '',
                    'description' => $extra['description'] ?? ($extra['name'] ?? ''),
                    'code' => $code,
                    'service_id' => $code,
                    'service_group' => $sd['service_group'] ?? '',
                    'service_type' => $sd['service_type'] ?? '',
                    'price' => (float) ($extra['daily_rate'] ?? 0),
                    'amount' => (float) ($extra['daily_rate'] ?? 0),
                    'daily_rate' => (float) ($extra['daily_rate'] ?? 0),
                    'total_price' => (float) ($extra['total_price'] ?? 0),
                    'price_per_day' => (float) ($extra['daily_rate'] ?? 0),
                    'currency' => $extra['currency'] ?? 'EUR',
                    'max_quantity' => $extra['max_quantity'] ?? 1,
                    'mandatory' => $extra['mandatory'] ?? false,
                    'required' => $extra['mandatory'] ?? false,
                    'type' => $extra['type'] ?? 'equipment',
                    'included' => ($sd['free_of_charge'] ?? false) || ($sd['included_in_price'] ?? false) || ($sd['included_in_price_limited'] ?? false),
                    'free_of_charge' => $sd['free_of_charge'] ?? false,
                    'included_in_price' => $sd['included_in_price'] ?? false,
                    'included_in_price_limited' => $sd['included_in_price_limited'] ?? false,
                    'is_one_time' => $sd['is_one_time'] ?? false,
                    'quantity_included' => $sd['quantity_included'] ?? 0,
                ];
            })->all();
        }

        $locautoExtras = $extras;
        if ($rawSupplierId === 'locauto_rent') {
            $locautoExtras = collect($gv['extras'] ?? [])->map(function ($extra) {
                $sd = $extra['supplier_data'] ?? [];
                return [
                    'id' => $extra['id'] ?? '',
                    'name' => $extra['name'] ?? '',
                    'description' => $extra['description'] ?? ($extra['name'] ?? ''),
                    'code' => $sd['code'] ?? ($extra['id'] ?? ''),
                    'amount' => (float) ($sd['amount'] ?? ($extra['total_price'] ?? 0)),
                    'price' => (float) ($extra['daily_rate'] ?? 0),
                    'daily_rate' => (float) ($extra['daily_rate'] ?? 0),
                    'total_price' => (float) ($extra['total_price'] ?? 0),
                    'total_for_booking' => (float) ($extra['total_price'] ?? 0),
                    'currency' => $extra['currency'] ?? 'EUR',
                    'mandatory' => $extra['mandatory'] ?? false,
                    'included_in_rate' => $sd['included_in_rate'] ?? false,
                    'type' => $extra['type'] ?? 'equipment',
                ];
            })->all();
        }

        $surpriceExtras = $extras;
        if ($rawSupplierId === 'surprice') {
            $surpriceExtras = collect($gv['extras'] ?? [])->map(function ($extra) {
                $sd = $extra['supplier_data'] ?? [];
                return [
                    'id' => $extra['id'] ?? '',
                    'name' => $extra['name'] ?? '',
                    'description' => $extra['description'] ?? ($extra['name'] ?? ''),
                    'code' => $sd['code'] ?? ($extra['id'] ?? ''),
                    'price' => (float) ($extra['total_price'] ?? 0),
                    'daily_rate' => (float) ($extra['daily_rate'] ?? 0),
                    'total_price' => (float) ($extra['total_price'] ?? 0),
                    'price_per_day' => (float) ($sd['unit_charge'] ?? ($extra['daily_rate'] ?? 0)),
                    'per_day' => $sd['per_day'] ?? false,
                    'allow_quantity' => $sd['allow_quantity'] ?? 1,
                    'purpose' => $sd['purpose'] ?? null,
                    'currency' => $extra['currency'] ?? 'EUR',
                    'mandatory' => $extra['mandatory'] ?? false,
                    'type' => $extra['type'] ?? 'equipment',
                ];
            })->all();
        }

        $protections = [];
        if ($rawSupplierId === 'adobe_car') {
            $protections = collect($gv['insurance_options'] ?? [])->map(function ($ins) {
                return [
                    'code' => strtoupper(str_replace(['ins_adobe_car_', 'ins_adobe_'], '', $ins['id'] ?? '')),
                    'name' => $ins['name'] ?? '',
                    'displayName' => $ins['name'] ?? '',
                    'description' => $ins['description'] ?? '',
                    'price' => $ins['total_price'] ?? 0,
                    'total' => $ins['total_price'] ?? 0,
                    'daily_rate' => $ins['daily_rate'] ?? 0,
                    'required' => $ins['included'] ?? false,
                    'excess_amount' => $ins['excess_amount'] ?? null,
                ];
            })->all();
        }

        $depositAmount = $pricing['deposit_amount']
            ?? ($supplierData['deposit_amount'] ?? ($supplierData['deposit'] ?? null));
        $depositCurrency = $pricing['deposit_currency']
            ?? ($supplierData['deposit_currency'] ?? null);

        $cancellationData = null;
        $cp = $gv['cancellation_policy'] ?? [];
        if (!empty($cp)) {
            $cancellationData = [
                'available' => $cp['free_cancellation'] ?? true,
                'penalty' => !($cp['free_cancellation'] ?? true),
                'amount' => $cp['cancellation_fee'] ?? 0,
                'currency' => $cp['cancellation_fee_currency'] ?? 'EUR',
                'deadline' => $cp['free_cancellation_until'] ?? null,
            ];
        }

        return [
            'id' => $gv['id'] ?? ($supplierId . '_' . $supplierVehicleId),
            'gateway_vehicle_id' => $gv['id'] ?? null,
            'provider_vehicle_id' => $supplierData['vehicle_category_id'] ?? ($supplierData['vehicle_id'] ?? ($supplierVehicleId ?: null)),
            'location_id' => $pickup['supplier_location_id'] ?? '',
            'source' => $supplierId,
            'brand' => $gv['make'] ?? '',
            'model' => $gv['model'] ?? '',
            'category' => $gv['category'] ?? 'other',
            'image' => $gv['image_url'] ?? '',
            'total_price' => (float) ($pricing['total_price'] ?? 0),
            'total' => (float) ($pricing['total_price'] ?? 0),
            'price_per_day' => (float) ($pricing['daily_rate'] ?? 0),
            'daily_rate' => (float) ($pricing['daily_rate'] ?? 0),
            'price_per_week' => (float) ($pricing['daily_rate'] ?? 0) * 7,
            'price_per_month' => (float) ($pricing['daily_rate'] ?? 0) * 30,
            'currency' => $pricing['currency'] ?? 'EUR',
            'transmission' => $transmission,
            'fuel' => $fuel,
            'seating_capacity' => $gv['seats'] ?? 5,
            'mileage' => $gv['mileage_policy'] ?? 'unlimited',
            'co2' => null,
            'latitude' => (float) ($pickup['latitude'] ?? 0),
            'longitude' => (float) ($pickup['longitude'] ?? 0),
            'full_vehicle_address' => $pickup['name'] ?? '',
            'provider_pickup_id' => $pickup['supplier_location_id'] ?? '',
            'provider_return_id' => ($gv['dropoff_location']['supplier_location_id'] ?? null) ?: ($pickup['supplier_location_id'] ?? ''),
            'provider_dropoff_id' => ($gv['dropoff_location']['supplier_location_id'] ?? null) ?: ($pickup['supplier_location_id'] ?? ''),
            'ok_mobility_pickup_time' => null,
            'ok_mobility_dropoff_time' => null,
            'features' => $features,
            'airConditioning' => $gv['air_conditioning'] ?? true,
            'sipp_code' => $gv['sipp_code'] ?? null,
            'doors' => $gv['doors'] ?? 4,
            'benefits' => [
                'minimum_driver_age' => $gv['min_driver_age'] ?? null,
                'maximum_driver_age' => $gv['max_driver_age'] ?? null,
                'fuel_policy' => $supplierData['fuel_policy'] ?? null,
                'deposit_amount' => $depositAmount,
                'deposit_currency' => $depositCurrency,
                'excess_amount' => !empty($gv['insurance_options']) ? ($gv['insurance_options'][0]['excess_amount'] ?? null) : null,
                'excess_theft_amount' => $supplierData['excess_theft_amount'] ?? ($supplierData['theft_excess'] ?? null),
            ],
            'provider_gross_amount' => !empty($supplierData['net_amount']) ? ($totalPrice) : null,
            'provider_net_amount' => $supplierData['net_amount'] ?? null,
            'provider_vat_amount' => $supplierData['vat_amount'] ?? null,
            'luggageSmall' => (string) ($gv['bags_small'] ?? 0),
            'luggageMed' => '0',
            'luggageLarge' => (string) ($gv['bags_large'] ?? 0),
            'products' => $this->buildGatewayProducts($rawSupplierId, $totalPrice, $dailyRate, $priceCurrency, $rentalDays, $gv),
            'tdr' => $supplierData['tdr'] ?? $totalPrice,
            'quoteid' => $supplierData['quote_id'] ?? null,
            'rentalCode' => null,
            'options' => ($rawSupplierId === 'ok_mobility') ? $okMobilityExtras : $options,
            'extras' => match ($rawSupplierId) {
                'sicily_by_car' => $sbcExtras,
                'ok_mobility' => $okMobilityExtras,
                'locauto_rent' => $locautoExtras,
                'surprice' => $surpriceExtras,
                'renteon' => $renteonExtras,
                default => $extras,
            },
            'insurance_options' => $insuranceOptions,
            'supplier_data' => $gv['supplier_data'] ?? [],
            'pickup_office' => $supplierData['pickup_office'] ?? null,
            'dropoff_office' => $supplierData['dropoff_office'] ?? null,
            'connector_id' => $supplierData['connector_id'] ?? null,
            'provider_pickup_office_id' => $supplierData['pickup_office_id'] ?? null,
            'provider_dropoff_office_id' => $supplierData['dropoff_office_id'] ?? null,
            'pricelist_id' => $supplierData['pricelist_id'] ?? null,
            'pricelist_code' => $supplierData['pricelist_code'] ?? null,
            'price_date' => $supplierData['price_date'] ?? null,
            'prepaid' => $supplierData['prepaid'] ?? false,
            'provider_code' => $supplierData['provider_code'] ?? $rawSupplierId,
            'is_on_request' => $supplierData['is_on_request'] ?? false,
            'pickup_station_name' => $supplierData['pickup_station_name'] ?? null,
            'dropoff_station_name' => $supplierData['dropoff_station_name'] ?? null,
            'pickup_address' => $supplierData['pickup_address'] ?? null,
            'dropoff_address' => $supplierData['dropoff_address'] ?? null,
            'fuel_policy' => $supplierData['fuel_policy'] ?? null,
            'cancellation' => $cancellationData,
            'extras_included' => $supplierData['extras_included'] ?? '',
            'extras_required' => $supplierData['extras_required'] ?? '',
            'extras_available' => $supplierData['extras_available'] ?? '',
            'ok_mobility_token' => $supplierData['token'] ?? null,
            'ok_mobility_group_id' => $supplierData['group_id'] ?? null,
            'ok_mobility_rate_code' => $supplierData['rate_code'] ?? null,
            'value_without_tax' => $supplierData['value_without_tax'] ?? null,
            'tax_rate' => $supplierData['tax_rate'] ?? null,
            'protections' => $protections,
            'office_address' => $supplierData['office_address'] ?? null,
            'office_phone' => $supplierData['office_phone'] ?? null,
            'office_schedule' => $supplierData['office_schedule'] ?? null,
            'at_airport' => $supplierData['at_airport'] ?? false,
            'pli' => $supplierData['pli'] ?? 0,
            'ldw' => $supplierData['ldw'] ?? 0,
            'spp' => $supplierData['spp'] ?? 0,
            'dro' => $supplierData['dro'] ?? 0,
            'deposit' => $supplierData['deposit'] ?? null,
            'availability_id' => $supplierData['availability_id'] ?? null,
            'rate_id' => $supplierData['rate_id'] ?? null,
            'rate_name' => $supplierData['rate_description'] ?? null,
            'payment_type' => $supplierData['rate_payment'] ?? null,
            'recordgo_acriss_id' => $supplierData['acriss_id'] ?? null,
            'recordgo_sellcode_ver' => $supplierData['sell_code_ver'] ?? null,
            'recordgo_country' => $supplierData['country'] ?? null,
            'recordgo_partner_user' => null,
            'bags' => ($gv['bags_large'] ?? 0) + ($gv['bags_small'] ?? 0) ?: null,
            'suitcases' => $gv['bags_large'] ?? null,
            'security_deposit' => $depositAmount,
        ];
    }

    public function normalizeSupplierId(string $supplierId): string
    {
        $map = [
            'green_motion' => 'greenmotion',
            'adobe_car' => 'adobe',
            'ok_mobility' => 'okmobility',
            'record_go' => 'recordgo',
            'u_save' => 'usave',
        ];

        return $map[$supplierId] ?? $supplierId;
    }

    private function buildGatewayProducts(string $supplierId, float $totalPrice, float $dailyRate, string $currency, int $rentalDays, array $gv): array
    {
        $supplierData = $gv['supplier_data'] ?? [];
        $rawProducts = $supplierData['products'] ?? [];
        if (!empty($rawProducts) && is_array($rawProducts)) {
            return collect($rawProducts)->map(function ($p) use ($rentalDays) {
                $total = (float) ($p['total'] ?? 0);
                return [
                    'type' => $p['type'] ?? 'BAS',
                    'name' => $this->getProductName($p['type'] ?? 'BAS'),
                    'total' => $total,
                    'price_per_day' => $rentalDays > 0 ? round($total / $rentalDays, 2) : $total,
                    'currency' => $p['currency'] ?? 'EUR',
                    'excess' => $p['excess'] ?? null,
                    'deposit' => $p['deposit'] ?? null,
                    'mileage' => $p['mileage'] ?? 0,
                    'costperextradistance' => $p['costperextradistance'] ?? null,
                    'fuelpolicy' => $p['fuelpolicy'] ?? '',
                    'minage' => $p['minage'] ?? 0,
                    'debitcard' => $p['debitcard'] ?? '',
                ];
            })->all();
        }

        $deposit = $gv['pricing']['deposit_amount'] ?? null;
        $excess = !empty($gv['insurance_options']) ? ($gv['insurance_options'][0]['excess_amount'] ?? null) : null;
        $mileagePolicy = $gv['mileage_policy'] ?? 'unlimited';

        $benefits = [];
        if ($mileagePolicy === 'unlimited') {
            $benefits[] = 'Unlimited Mileage';
        } elseif ($mileagePolicy === 'limited') {
            $limitKm = $gv['mileage_limit_km'] ?? null;
            $benefits[] = $limitKm ? "Limited: {$limitKm} km" : 'Limited Mileage';
        }
        if ($deposit) {
            $benefits[] = 'Deposit: ' . $currency . ' ' . number_format($deposit, 2);
        }
        if ($excess) {
            $benefits[] = 'Excess: ' . $currency . ' ' . number_format($excess, 2);
        }

        return [[
            'type' => 'BAS',
            'name' => 'Basic Package',
            'total' => $totalPrice,
            'price_per_day' => $dailyRate,
            'currency' => $currency,
            'excess' => $excess,
            'deposit' => $deposit,
            'benefits' => $benefits,
        ]];
    }

    private function getProductName(string $type): string
    {
        return match (strtoupper($type)) {
            'BAS' => 'Basic',
            'MED' => 'Medium',
            'PRE' => 'Premium',
            'PMP' => 'Premium Plus',
            'PLU' => 'Plus',
            default => ucfirst(strtolower($type)),
        };
    }
}
