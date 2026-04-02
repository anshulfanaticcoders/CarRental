<?php

namespace App\Services\Vehicles;

class GatewayVehicleTransformer
{
    public function transform(array $gv, int $rentalDays): array
    {
        if ($this->isCanonicalSearchVehiclePayload($gv)) {
            return $this->transformCanonicalSearchVehicle($gv, $rentalDays);
        }

        $rawSupplierId = $gv['supplier_id'] ?? 'unknown';
        $supplierId = $this->normalizeSupplierId((string) $rawSupplierId);
        $supplierVehicleId = $gv['supplier_vehicle_id'] ?? '';
        $pricing = $gv['pricing'] ?? [];
        $pickup = $gv['pickup_location'] ?? [];
        $supplierData = $gv['supplier_data'] ?? [];
        $providerVehicleId = $this->resolveProviderVehicleId($rawSupplierId, $gv, $supplierData, $supplierVehicleId);
        $imageUrl = $this->resolveImageUrl($rawSupplierId, $gv);
        $totalPrice = (float) ($pricing['total_price'] ?? 0);
        $dailyRate = (float) ($pricing['daily_rate'] ?? 0);
        $priceCurrency = $pricing['currency'] ?? 'EUR';

        // Adobe: `tdr` in supplier_data is the daily rate, not the booking total.
        // The gateway pricing already has the correct total_price and daily_rate.
        // No override needed — use the gateway pricing as-is.

        $transmission = $gv['transmission'] ?? null;

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
        if (($gv['air_conditioning'] ?? null) === true) {
            $features[] = 'Air Conditioning';
        }

        $extras = $this->mapExtras($gv['extras'] ?? [], $rawSupplierId);
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
            'provider_vehicle_id' => $providerVehicleId,
            'provider_product_id' => $gv['provider_product_id'] ?? ($supplierData['product_id'] ?? null),
            'provider_rate_id' => $gv['provider_rate_id'] ?? ($supplierData['rate_id'] ?? ($supplierData['vendor_rate_id'] ?? null)),
            'availability_status' => $gv['availability_status'] ?? ($supplierData['availability_status'] ?? null),
            'location_id' => $pickup['supplier_location_id'] ?? '',
            'source' => $supplierId,
            'brand' => $gv['make'] ?? '',
            'model' => $gv['model'] ?? '',
            'category' => $gv['category'] ?? 'other',
            'image' => $imageUrl,
            'total_price' => $totalPrice,
            'total' => $totalPrice,
            'price_per_day' => $dailyRate,
            'daily_rate' => $dailyRate,
            'price_per_week' => $dailyRate * 7,
            'price_per_month' => $dailyRate * 30,
            'currency' => $priceCurrency,
            'transmission' => $transmission,
            'fuel' => $fuel,
            'seating_capacity' => $gv['seats'] ?? null,
            'mileage' => $gv['mileage_policy'] ?? null,
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
            'airConditioning' => $gv['air_conditioning'] ?? null,
            'sipp_code' => $gv['sipp_code'] ?? null,
            'doors' => $gv['doors'] ?? null,
            'benefits' => $this->buildBenefits($rawSupplierId, $gv, $supplierData, $depositAmount, $depositCurrency),
            'provider_gross_amount' => !empty($supplierData['net_amount']) ? ($totalPrice) : null,
            'provider_net_amount' => $supplierData['net_amount'] ?? null,
            'provider_vat_amount' => $supplierData['vat_amount'] ?? null,
            'luggageSmall' => array_key_exists('bags_small', $gv) ? (string) $gv['bags_small'] : null,
            'luggageMed' => null,
            'luggageLarge' => array_key_exists('bags_large', $gv) ? (string) $gv['bags_large'] : null,
            'products' => $this->buildGatewayProducts($rawSupplierId, $totalPrice, $dailyRate, $priceCurrency, $rentalDays, $gv),
            'tdr' => $supplierData['tdr'] ?? $totalPrice,
            'quoteid' => $supplierData['quote_id'] ?? null,
            'rentalCode' => null,
            'options' => $options,
            'extras' => $extras,
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
            'pickup_address' => $supplierData['pickup_address'] ?? ($pickup['address'] ?? null),
            'dropoff_address' => $supplierData['dropoff_address'] ?? (($gv['dropoff_location']['address'] ?? null) ?: ($pickup['address'] ?? null)),
            'pickup_instructions' => $supplierData['pickup_instructions'] ?? ($pickup['pickup_instructions'] ?? null),
            'dropoff_instructions' => $supplierData['dropoff_instructions'] ?? (($gv['dropoff_location']['dropoff_instructions'] ?? null) ?: ($pickup['dropoff_instructions'] ?? null)),
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
            'office_address' => $supplierData['office_address'] ?? ($pickup['address'] ?? null),
            'office_phone' => $supplierData['office_phone'] ?? ($pickup['phone'] ?? null),
            'office_schedule' => $supplierData['office_schedule'] ?? ($pickup['operating_hours'] ?? null),
            'at_airport' => $supplierData['at_airport'] ?? ($pickup['is_airport'] ?? false),
            'location_details' => [
                'name' => $supplierData['pickup_station_name'] ?? $supplierData['office_name'] ?? ($pickup['name'] ?? null),
                'address_1' => $supplierData['pickup_address'] ?? $supplierData['office_address'] ?? ($pickup['address'] ?? null),
                'telephone' => $supplierData['pickup_phone'] ?? $supplierData['office_phone'] ?? ($pickup['phone'] ?? null),
                'email' => $supplierData['pickup_email'] ?? ($pickup['email'] ?? null),
                'latitude' => $pickup['latitude'] ?? null,
                'longitude' => $pickup['longitude'] ?? null,
                'opening_hours' => $supplierData['pickup_hours'] ?? $supplierData['office_schedule'] ?? ($pickup['operating_hours'] ?? null),
                'out_of_hours' => $supplierData['pickup_out_of_hours'] ?? null,
                'collection_details' => $supplierData['pickup_instructions'] ?? ($pickup['pickup_instructions'] ?? null),
                'dropoff_instructions' => $supplierData['dropoff_instructions'] ?? ($pickup['dropoff_instructions'] ?? null),
            ],
            'location_instructions' => $supplierData['pickup_instructions'] ?? ($pickup['pickup_instructions'] ?? null),
            'pli' => $supplierData['pli'] ?? 0,
            'ldw' => $supplierData['ldw'] ?? 0,
            'spp' => $supplierData['spp'] ?? 0,
            'deposit' => $supplierData['deposit'] ?? null,
            'availability_id' => $supplierData['availability_id'] ?? null,
            'rate_id' => $supplierData['rate_id'] ?? null,
            'rate_name' => $supplierData['rate_description'] ?? null,
            'payment_type' => $supplierData['rate_payment'] ?? null,
            'recordgo_products' => $this->extractRecordGoProducts($rawSupplierId, $supplierData),
            'recordgo_acriss_id' => $supplierData['acriss_id'] ?? null,
            'recordgo_sellcode_ver' => $supplierData['sell_code_ver'] ?? null,
            'recordgo_country' => $supplierData['country'] ?? null,
            'terms' => $this->extractTerms($rawSupplierId, $supplierData),
            'driver_requirements' => $this->extractDriverRequirements($rawSupplierId, $supplierData, $gv),
            'rental_policies' => $this->extractRentalPolicies($rawSupplierId, $gv),
            'bags' => array_key_exists('bags_large', $gv) || array_key_exists('bags_small', $gv)
                ? (($gv['bags_large'] ?? 0) + ($gv['bags_small'] ?? 0))
                : null,
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

    private function extractRecordGoProducts(string $supplierId, array $supplierData): array
    {
        if (!in_array($supplierId, ['record_go', 'recordgo'], true)) {
            return [];
        }

        $products = $supplierData['products'] ?? null;
        if (is_array($products) && $products !== []) {
            return array_values(array_filter($products, fn ($product) => is_array($product)));
        }

        $productData = $supplierData['product_data'] ?? null;
        if (is_array($productData) && $productData !== []) {
            return [$productData];
        }

        return [];
    }

    private function extractTerms(string $supplierId, array $supplierData): ?array
    {
        // RecordGo: product T&Cs from productTTCC
        if (in_array($supplierId, ['record_go', 'recordgo'], true)) {
            $productData = $supplierData['product_data'] ?? ($supplierData['products'][0] ?? null);
            $ttcc = $productData['terms'] ?? '';
            if ($ttcc && is_string($ttcc) && trim($ttcc) !== '') {
                return [['name' => 'Rental Terms & Conditions', 'conditions' => [strip_tags($ttcc)]]];
            }
        }

        // Locauto: comprehensive T&Cs from conditions docs
        if (in_array($supplierId, ['locauto_rent', 'locauto'], true)) {
            $sipp = $gv['sipp_code'] ?? ($supplierData['sipp_code'] ?? '');
            $conditions = $this->getLocautoConditions($sipp);
            $minAge = $conditions['min_age'] ?? 19;
            $damageExcess = $conditions['damage_excess'] ?? '';
            $theftExcess = $conditions['theft_excess'] ?? '';

            return [
                ['name' => 'Payment Methods', 'conditions' => [
                    'Credit Cards or Debit Cards accepted (Visa, Mastercard, Amex, Union Pay).',
                    'Maestro, prepaid or rechargeable cards are NOT accepted.',
                    'Card must be in the main driver\'s name with sufficient funds for deposit + rental charges.',
                    'Digital cards are not accepted.',
                ]],
                ['name' => 'Documents Required', 'conditions' => [
                    'Valid ID card or passport with residence data (digital documents not accepted).',
                    'Valid driving license held for minimum 1 year (digital license not accepted).',
                    'Non-EU licenses require International Driving Permit (IDP) or official translation.',
                ]],
                ['name' => 'Deposit & Excess', 'conditions' => [
                    "Damage excess (CDW): €{$damageExcess}",
                    "Theft excess (TW): €{$theftExcess}",
                    'Deposit blocked on credit card at pickup.',
                ]],
                ['name' => 'Fuel & Mileage', 'conditions' => [
                    'Fuel policy: Full to Full. Return with same fuel level. Refuelling surcharge: €35.',
                    'Unlimited mileage for rentals up to 27 days.',
                    '28+ days: 117 km/day (3,500 km/month). Extra km: €0.24–€0.30 + fees + VAT.',
                ]],
                ['name' => 'Age Requirements', 'conditions' => [
                    "Minimum driver age: {$minAge} years.",
                    'Young driver surcharge (19–24 years): €15/day (up to 10 days).',
                ]],
                ['name' => 'Pickup & Grace Period', 'conditions' => [
                    'Vehicle kept available for 59 minutes after booked time.',
                    'For airport/station bookings: provide flight/train number for 59-min grace after landing.',
                    'Out of hours pickup: €30/rental (on request, max 90 min after closing).',
                ]],
                ['name' => 'Drop-off', 'conditions' => [
                    '59-minute grace period from pickup time on rental agreement.',
                ]],
                ['name' => 'Cross Border', 'conditions' => [
                    'Allowed in all EU countries + Norway, Switzerland, UK. No extra charge.',
                    'Prior authorisation recommended to booking@locautorent.it.',
                    'One-way outside Italy not allowed without authorisation (€2,000 penalty).',
                ]],
                ['name' => 'Cancellation', 'conditions' => [
                    'No-show fee for groups S, L, R, RR, P: €50.',
                    'Late cancellation (within 24h before pickup) for groups S, L, R, RR, P: €50.',
                ]],
                ['name' => 'Vehicle', 'conditions' => [
                    'Specific make/model not guaranteed. Vehicle category (SIPP code) is guaranteed.',
                    'Voucher required at pickup (paper or digital accepted). Max 30 days per voucher.',
                ]],
            ];
        }

        return null;
    }

    private function extractDriverRequirements(string $supplierId, array $supplierData, array $gv): ?array
    {
        $requirements = [];

        $productData = $supplierData['product_data'] ?? [];
        $minAge = $gv['min_driver_age'] ?? ($supplierData['min_driver_age'] ?? ($productData['min_age'] ?? null));
        $maxAge = $gv['max_driver_age'] ?? ($supplierData['max_driver_age'] ?? ($productData['max_age'] ?? null));
        $minLicense = $productData['min_driver_license'] ?? ($supplierData['driving_license_age'] ?? null);

        // Keys become display labels via key.replace(/_/g, ' ') in frontend
        // Values must be '1' to pass the boolean filter
        if ($minAge) {
            $requirements["Minimum_driver_age:_{$minAge}_years"] = '1';
        }
        if ($maxAge) {
            $requirements["Maximum_driver_age:_{$maxAge}_years"] = '1';
        }
        if ($minLicense) {
            $requirements["Driving_licence_held_for_at_least_{$minLicense}_year(s)"] = '1';
        }

        // Locauto — only driver-related requirements here
        if ($supplierId === 'locauto_rent' || $supplierId === 'locauto') {
            $conditions = $this->getLocautoConditions($gv['sipp_code'] ?? ($supplierData['sipp_code'] ?? ''));
            if ($conditions && ($conditions['min_age'] ?? null)) {
                $requirements["Minimum_driver_age:_{$conditions['min_age']}_years"] = '1';
            }
            $requirements['Valid_driving_license_(held_min_1_year)'] = '1';
            $requirements['Valid_ID_or_passport_required'] = '1';
            $requirements['mileage_type'] = 'Unlimited';
        }

        // Mileage — special key, excluded from items list, shown as label
        $policies = $gv['policies'] ?? [];
        $mileagePolicy = $gv['mileage_policy'] ?? ($policies['mileage_policy'] ?? null);
        $mileageLimitKm = $gv['mileage_limit_km'] ?? ($policies['mileage_limit_km'] ?? null);
        if ($mileagePolicy && !isset($requirements['mileage_type'])) {
            if ($mileagePolicy === 'limited' && $mileageLimitKm) {
                $requirements['mileage_type'] = "Limited ({$mileageLimitKm} km)";
            } else {
                $requirements['mileage_type'] = ucfirst($mileagePolicy);
            }
        }

        return !empty($requirements) ? $requirements : null;
    }

    private function buildBenefits(string $rawSupplierId, array $gv, array $supplierData, $depositAmount, $depositCurrency): array
    {
        $benefits = [
            'minimum_driver_age' => $gv['min_driver_age'] ?? ($supplierData['min_driver_age'] ?? null),
            'maximum_driver_age' => $gv['max_driver_age'] ?? ($supplierData['max_driver_age'] ?? null),
            'fuel_policy' => $supplierData['fuel_policy'] ?? ($gv['policies']['fuel_policy'] ?? null),
            'deposit_amount' => $depositAmount,
            'deposit_currency' => $depositCurrency,
            'excess_amount' => !empty($gv['insurance_options']) ? ($gv['insurance_options'][0]['excess_amount'] ?? null) : null,
            'excess_theft_amount' => $supplierData['excess_theft_amount'] ?? ($supplierData['theft_excess'] ?? null),
        ];

        // Locauto: enrich from conditions YAML
        if (in_array($rawSupplierId, ['locauto_rent', 'locauto'])) {
            $sipp = $gv['sipp_code'] ?? ($gv['specs']['sipp_code'] ?? ($supplierData['sipp_code'] ?? ''));
            $conditions = $this->getLocautoConditions($sipp);
            if ($conditions) {
                $benefits['minimum_driver_age'] = $benefits['minimum_driver_age'] ?? $conditions['min_age'];
                $benefits['excess_amount'] = $benefits['excess_amount'] ?? $conditions['damage_excess'];
                $benefits['excess_theft_amount'] = $benefits['excess_theft_amount'] ?? $conditions['theft_excess'];
                $benefits['fuel_policy'] = $benefits['fuel_policy'] ?? 'Full to Full';
            }
        }

        return $benefits;
    }

    private function extractRentalPolicies(string $supplierId, array $gv): ?array
    {
        if ($supplierId !== 'locauto_rent' && $supplierId !== 'locauto') {
            return null;
        }

        $sippCode = $gv['sipp_code'] ?? ($gv['supplier_data']['sipp_code'] ?? '');
        $conditions = $this->getLocautoConditions($sippCode);
        if (!$conditions) {
            return null;
        }

        return [
            ['label' => 'Fuel Policy', 'value' => 'Full to Full', 'detail' => 'Return with full tank. Refuelling surcharge: €35'],
            ['label' => 'Mileage', 'value' => 'Unlimited'],
            ['label' => 'Damage Excess (CDW)', 'value' => '€' . $conditions['damage_excess']],
            ['label' => 'Theft Excess (TW)', 'value' => '€' . $conditions['theft_excess']],
            ['label' => 'Payment at Pickup', 'value' => 'Credit or Debit card in driver\'s name'],
            ['label' => 'Pickup Grace Period', 'value' => '59 minutes from booked time'],
            ['label' => 'Drop-off Grace Period', 'value' => '59 minutes from pickup time on agreement'],
            ['label' => 'Out of Hours Fee', 'value' => '€30 per rental (on request, max 90 min after closing)'],
            ['label' => 'Cross Border', 'value' => 'Allowed in EU + Norway + Switzerland (prior authorisation required)'],
        ];
    }

    private function getLocautoConditions(string $sippCode): ?array
    {
        // Per-SIPP conditions from Locauto T&Cs
        $conditions = [
            'MBMR' => ['min_age' => 19, 'damage_excess' => 1200, 'theft_excess' => 1800],
            'MDMR' => ['min_age' => 19, 'damage_excess' => 1200, 'theft_excess' => 1800],
            'EDMR' => ['min_age' => 19, 'damage_excess' => 1200, 'theft_excess' => 1800],
            'CDMR' => ['min_age' => 21, 'damage_excess' => 1500, 'theft_excess' => 2000],
            'CXMR' => ['min_age' => 21, 'damage_excess' => 1500, 'theft_excess' => 2000],
            'IDAR' => ['min_age' => 21, 'damage_excess' => 1700, 'theft_excess' => 2200],
            'CDAR' => ['min_age' => 21, 'damage_excess' => 1700, 'theft_excess' => 2200],
            'CFAR' => ['min_age' => 21, 'damage_excess' => 1500, 'theft_excess' => 2000],
            'CFMR' => ['min_age' => 21, 'damage_excess' => 1500, 'theft_excess' => 2000],
            'EDAR' => ['min_age' => 21, 'damage_excess' => 1500, 'theft_excess' => 2000],
            'LWAR' => ['min_age' => 27, 'damage_excess' => 2000, 'theft_excess' => 2800],
            'PWAR' => ['min_age' => 27, 'damage_excess' => 2000, 'theft_excess' => 2800],
            'FVMR' => ['min_age' => 25, 'damage_excess' => 2000, 'theft_excess' => 2800],
            'SWAR' => ['min_age' => 25, 'damage_excess' => 1700, 'theft_excess' => 2200],
            'CWAR' => ['min_age' => 25, 'damage_excess' => 1700, 'theft_excess' => 2200],
            'SWMR' => ['min_age' => 25, 'damage_excess' => 1700, 'theft_excess' => 2200],
            'IFAR' => ['min_age' => 25, 'damage_excess' => 1700, 'theft_excess' => 2200],
        ];

        return $conditions[$sippCode] ?? null;
    }

    private function resolveProviderVehicleId(string $rawSupplierId, array $gv, array $supplierData, mixed $supplierVehicleId): ?string
    {
        if ($rawSupplierId === 'renteon') {
            $parts = collect([
                $supplierData['provider_code'] ?? null,
                $gv['make'] ?? null,
                $gv['model'] ?? null,
            ])
                ->filter(fn ($value) => is_string($value) && trim($value) !== '')
                ->map(fn ($value) => mb_strtolower(trim($value)))
                ->values()
                ->all();

            if ($parts !== []) {
                return implode('|', $parts);
            }
        }

        $resolved = $supplierData['vehicle_category_id'] ?? ($supplierData['vehicle_id'] ?? ($supplierVehicleId ?: null));

        return $resolved === '' ? null : $resolved;
    }

    private function resolveImageUrl(string $rawSupplierId, array $gv): string
    {
        // Renteon's CarModelImageURL is not reliable enough for customer-facing cards.
        // Falling back to the generic UI placeholder is safer than showing the wrong car.
        if ($rawSupplierId === 'renteon') {
            return '';
        }

        return (string) ($gv['image_url'] ?? '');
    }

    private function isCanonicalSearchVehiclePayload(array $gv): bool
    {
        return array_key_exists('display_name', $gv)
            && array_key_exists('pricing', $gv)
            && array_key_exists('location', $gv);
    }

    private function transformCanonicalSearchVehicle(array $gv, int $rentalDays): array
    {
        $source = $this->normalizeSupplierId((string) ($gv['source'] ?? 'unknown'));
        $pricing = is_array($gv['pricing'] ?? null) ? $gv['pricing'] : [];
        $specs = is_array($gv['specs'] ?? null) ? $gv['specs'] : [];
        $policies = is_array($gv['policies'] ?? null) ? $gv['policies'] : [];
        $location = is_array($gv['location'] ?? null) ? $gv['location'] : [];
        $pickup = is_array($location['pickup'] ?? null) ? $location['pickup'] : [];
        $dropoff = is_array($location['dropoff'] ?? null) ? $location['dropoff'] : [];
        $bookingContext = is_array($gv['booking_context'] ?? null) ? $gv['booking_context'] : [];
        $providerPayload = is_array($bookingContext['provider_payload'] ?? null) ? $bookingContext['provider_payload'] : [];
        $extrasPreview = is_array($gv['extras_preview'] ?? null) ? $gv['extras_preview'] : [];
        // Fallback: if no extras_preview, use full extras array (e.g. OKMobility, Locauto)
        $fullExtras = is_array($gv['extras'] ?? null) ? $gv['extras'] : [];
        $products = is_array($gv['products'] ?? null) ? $gv['products'] : [];
        $image = (string) ($gv['image'] ?? '');
        $totalPrice = (float) ($pricing['total_price'] ?? 0);
        $pricePerDay = $pricing['price_per_day'] ?? $pricing['daily_rate'] ?? null;
        $pricePerDay = $pricePerDay !== null ? (float) $pricePerDay : ($rentalDays > 0 ? round($totalPrice / $rentalDays, 2) : $totalPrice);
        $currency = $pricing['currency'] ?? 'EUR';
        $fuel = $specs['fuel'] ?? null;
        $airConditioning = array_key_exists('air_conditioning', $specs) ? $specs['air_conditioning'] : null;

        return [
            'id' => $gv['id'] ?? null,
            'gateway_vehicle_id' => $gv['gateway_vehicle_id'] ?? ($gv['id'] ?? null),
            'provider_vehicle_id' => $gv['provider_vehicle_id'] ?? null,
            'provider_product_id' => $gv['provider_product_id'] ?? ($providerPayload['product_id'] ?? null),
            'provider_rate_id' => $gv['provider_rate_id'] ?? ($providerPayload['rate_id'] ?? ($providerPayload['vendor_rate_id'] ?? null)),
            'availability_status' => $gv['availability_status'] ?? ($providerPayload['availability_status'] ?? ($providerPayload['availability'] ?? null)),
            'location_id' => $pickup['provider_location_id'] ?? '',
            'source' => $source,
            'brand' => $gv['brand'] ?? '',
            'model' => $gv['model'] ?? '',
            'display_name' => $gv['display_name'] ?? '',
            'category' => $gv['category'] ?? 'other',
            'image' => $image,
            'total_price' => $totalPrice,
            'total' => $totalPrice,
            'price_per_day' => $pricePerDay,
            'daily_rate' => $pricePerDay,
            'price_per_week' => $pricePerDay * 7,
            'price_per_month' => $pricePerDay * 30,
            'currency' => $currency,
            'transmission' => $specs['transmission'] ?? null,
            'fuel' => $fuel !== null ? ucfirst((string) $fuel) : null,
            'seating_capacity' => $specs['seating_capacity'] ?? null,
            'mileage' => $policies['mileage_policy'] ?? null,
            'co2' => null,
            'latitude' => isset($pickup['latitude']) ? (float) $pickup['latitude'] : 0.0,
            'longitude' => isset($pickup['longitude']) ? (float) $pickup['longitude'] : 0.0,
            'full_vehicle_address' => $pickup['name'] ?? '',
            'provider_pickup_id' => $pickup['provider_location_id'] ?? '',
            'provider_return_id' => $dropoff['provider_location_id'] ?? ($pickup['provider_location_id'] ?? ''),
            'provider_dropoff_id' => $dropoff['provider_location_id'] ?? ($pickup['provider_location_id'] ?? ''),
            'ok_mobility_pickup_time' => null,
            'ok_mobility_dropoff_time' => null,
            'features' => $airConditioning === true ? ['Air Conditioning'] : [],
            'airConditioning' => $airConditioning,
            'sipp_code' => $specs['sipp_code'] ?? null,
            'doors' => $specs['doors'] ?? null,
            'benefits' => $this->buildBenefits($source, $gv, $providerPayload, $pricing['deposit_amount'] ?? null, $pricing['deposit_currency'] ?? null),
            'provider_gross_amount' => null,
            'provider_net_amount' => $providerPayload['net_amount'] ?? null,
            'provider_vat_amount' => $providerPayload['vat_amount'] ?? null,
            'luggageSmall' => isset($specs['luggage_small']) ? (string) $specs['luggage_small'] : null,
            'luggageMed' => isset($specs['luggage_medium']) ? (string) $specs['luggage_medium'] : null,
            'luggageLarge' => isset($specs['luggage_large']) ? (string) $specs['luggage_large'] : null,
            'products' => !empty($products)
                ? $this->mapCanonicalProducts($products, $currency, $pricePerDay)
                : [['type' => 'BAS', 'name' => 'Basic Package', 'total' => $totalPrice, 'price_per_day' => $pricePerDay, 'currency' => $currency]],
            'tdr' => $providerPayload['tdr'] ?? $totalPrice,
            'quoteid' => $providerPayload['quote_id'] ?? null,
            'rentalCode' => null,
            'options' => !empty($extrasPreview)
                ? $this->mapCanonicalExtrasPreview($extrasPreview)
                : $this->mapExtras($fullExtras, $source),
            'extras' => !empty($extrasPreview)
                ? $this->mapCanonicalExtrasPreview($extrasPreview)
                : $this->mapExtras($fullExtras, $source),
            'insurance_options' => $this->mapCanonicalInsuranceOptions($gv, $providerPayload),
            'supplier_data' => $providerPayload,
            'pickup_office' => $providerPayload['pickup_office'] ?? null,
            'dropoff_office' => $providerPayload['dropoff_office'] ?? null,
            'connector_id' => $providerPayload['connector_id'] ?? null,
            'provider_pickup_office_id' => $providerPayload['pickup_office_id'] ?? null,
            'provider_dropoff_office_id' => $providerPayload['dropoff_office_id'] ?? null,
            'pricelist_id' => $providerPayload['pricelist_id'] ?? null,
            'pricelist_code' => $providerPayload['pricelist_code'] ?? null,
            'price_date' => $providerPayload['price_date'] ?? null,
            'prepaid' => $providerPayload['prepaid'] ?? false,
            'provider_code' => $gv['provider_code'] ?? $source,
            'is_on_request' => $providerPayload['is_on_request'] ?? false,
            'pickup_station_name' => $providerPayload['pickup_station_name'] ?? null,
            'dropoff_station_name' => $providerPayload['dropoff_station_name'] ?? null,
            'pickup_address' => $providerPayload['pickup_address'] ?? ($pickup['address'] ?? null),
            'dropoff_address' => $providerPayload['dropoff_address'] ?? (($dropoff['address'] ?? null) ?: ($pickup['address'] ?? null)),
            'pickup_instructions' => $providerPayload['pickup_instructions'] ?? ($pickup['pickup_instructions'] ?? null),
            'dropoff_instructions' => $providerPayload['dropoff_instructions'] ?? (($dropoff['dropoff_instructions'] ?? null) ?: ($pickup['dropoff_instructions'] ?? null)),
            'fuel_policy' => $policies['fuel_policy'] ?? null,
            'cancellation' => $policies['cancellation'] ?? null,
            'extras_included' => $providerPayload['extras_included'] ?? '',
            'extras_required' => $providerPayload['extras_required'] ?? '',
            'extras_available' => $providerPayload['extras_available'] ?? '',
            'ok_mobility_token' => $providerPayload['token'] ?? null,
            'ok_mobility_group_id' => $providerPayload['group_id'] ?? null,
            'ok_mobility_rate_code' => $providerPayload['rate_code'] ?? null,
            'value_without_tax' => $providerPayload['value_without_tax'] ?? null,
            'tax_rate' => $providerPayload['tax_rate'] ?? null,
            'protections' => [],
            'office_address' => $providerPayload['office_address'] ?? ($pickup['address'] ?? null),
            'office_phone' => $providerPayload['office_phone'] ?? ($pickup['phone'] ?? null),
            'office_schedule' => $providerPayload['office_schedule'] ?? ($pickup['operating_hours'] ?? null),
            'at_airport' => $providerPayload['at_airport'] ?? ($pickup['is_airport'] ?? false),
            'pli' => $providerPayload['pli'] ?? 0,
            'ldw' => $providerPayload['ldw'] ?? 0,
            'spp' => $providerPayload['spp'] ?? 0,
            'deposit' => $providerPayload['deposit'] ?? null,
            'availability_id' => $providerPayload['availability_id'] ?? null,
            'rate_id' => $providerPayload['rate_id'] ?? null,
            'rate_name' => $providerPayload['rate_description'] ?? null,
            'payment_type' => $providerPayload['rate_payment'] ?? null,
            'recordgo_products' => $this->extractRecordGoProducts($source, $providerPayload),
            'recordgo_acriss_id' => $providerPayload['acriss_id'] ?? null,
            'recordgo_sellcode_ver' => $providerPayload['sell_code_ver'] ?? null,
            'recordgo_country' => $providerPayload['country'] ?? null,
            'terms' => $this->extractTerms($source, $providerPayload),
            'driver_requirements' => $this->extractDriverRequirements($source, $providerPayload, $gv),
            'bags' => (($specs['luggage_small'] ?? 0) + ($specs['luggage_medium'] ?? 0) + ($specs['luggage_large'] ?? 0)) ?: null,
            'suitcases' => $specs['luggage_large'] ?? null,
            'security_deposit' => $pricing['deposit_amount'] ?? null,
            'specs' => $specs,
            'pricing' => [
                'currency' => $currency,
                'price_per_day' => $pricePerDay,
                'total_price' => $totalPrice,
                'deposit_amount' => $pricing['deposit_amount'] ?? null,
                'deposit_currency' => $pricing['deposit_currency'] ?? null,
                'excess_amount' => $pricing['excess_amount'] ?? null,
                'excess_theft_amount' => $pricing['excess_theft_amount'] ?? null,
            ],
            'policies' => $policies,
            'location' => $location,
            'data_quality_flags' => $gv['data_quality_flags'] ?? [],
            'pricing_transparency_flags' => $gv['pricing_transparency_flags'] ?? [],
            'ui_placeholders' => $gv['ui_placeholders'] ?? ['image' => $image === ''],
            'booking_context' => $bookingContext,
        ];
    }

    private function mapCanonicalProducts(array $products, string $currency, float $fallbackDailyRate): array
    {
        return collect($products)->filter(fn ($product) => is_array($product))->map(function ($product) use ($currency, $fallbackDailyRate) {
            return [
                'type' => $product['type'] ?? 'BAS',
                'name' => $product['name'] ?? $this->getProductName($product['type'] ?? 'BAS'),
                'total' => (float) ($product['total'] ?? 0),
                'price_per_day' => (float) ($product['price_per_day'] ?? $fallbackDailyRate),
                'currency' => $product['currency'] ?? $currency,
                'excess' => $product['excess'] ?? null,
                'deposit' => $product['deposit'] ?? null,
                'mileage' => $product['mileage_limit_km'] ?? null,
                'costperextradistance' => $product['cost_per_extra_km'] ?? null,
                'fuelpolicy' => $product['fuel_policy'] ?? '',
                'minage' => $product['minimum_driver_age'] ?? 0,
                'debitcard' => $product['debit_card_required'] ?? '',
                'benefits' => $product['benefits'] ?? [],
            ];
        })->values()->all();
    }

    private function mapExtras(array $rawExtras, string $supplierId): array
    {
        return collect($rawExtras)->map(function (array $extra) use ($supplierId) {
            $sd = $extra['supplier_data'] ?? [];
            $extId = $extra['id'] ?? '';
            $name = $extra['name'] ?? '';
            $description = $extra['description'] ?? $name;
            $code = $sd['code'] ?? $extId;
            $dailyRate = (float) ($extra['daily_rate'] ?? 0);
            $totalPrice = (float) ($extra['total_price'] ?? 0);
            $extCurrency = $extra['currency'] ?? 'EUR';
            $maxQty = $extra['max_quantity'] ?? 1;
            $mandatory = $extra['mandatory'] ?? false;
            $type = $extra['type'] ?? 'equipment';
            $perDay = $sd['per_day'] ?? false;
            $pricePerDay = (float) ($sd['unit_charge'] ?? $dailyRate);

            // Canonical fields — every extra has these regardless of provider.
            $canonical = [
                'id' => $extId,
                'name' => $name,
                'description' => $description,
                'code' => $code,
                'price' => $dailyRate,
                'daily_rate' => $dailyRate,
                'total_price' => $totalPrice,
                'price_per_day' => $pricePerDay,
                'currency' => $extCurrency,
                'mandatory' => $mandatory,
                'type' => $type,
                'max_quantity' => $maxQty,
                'per_day' => $perDay,
                // Legacy aliases for backward compatibility with frontend code.
                'Daily_rate' => $dailyRate,
                'total_for_booking' => $totalPrice,
                'Total_for_this_booking' => $totalPrice,
                'total_for_booking_currency' => $extCurrency,
                'Total_for_this_booking_currency' => $extCurrency,
                'option_id' => $extId,
                'optionID' => $extId,
                'amount' => $dailyRate,
                'service_id' => $extId,
                'numberAllowed' => $maxQty,
                'required' => $mandatory,
            ];

            // Provider-specific enrichment from supplier_data.
            $enrichment = $this->enrichExtra($supplierId, $extra, $sd);

            // Protect canonical keys from accidental $sd collision.
            // Exception: 'id' is intentionally overridable (SBC adapter reads supplier_data.id
            // as the protection-plan code for protection matching, e.g. 'CDW').
            $protectedKeys = ['name', 'description', 'code', 'price', 'daily_rate', 'total_price',
                'price_per_day', 'currency', 'mandatory', 'type', 'max_quantity', 'per_day'];
            $sdSafe = array_diff_key($sd, array_flip($protectedKeys));

            // Merge: canonical first, then safe supplier_data raw fields, then enrichment.
            // Enrichment wins over raw supplier_data when keys overlap (computed values).
            return array_merge($canonical, $sdSafe, $enrichment);
        })->all();
    }

    private function enrichExtra(string $supplierId, array $extra, array $sd): array
    {
        return match ($supplierId) {
            'renteon' => [
                'included' => ($sd['free_of_charge'] ?? false)
                    || ($sd['included_in_price'] ?? false)
                    || ($sd['included_in_price_limited'] ?? false),
            ],
            'locauto_rent' => [
                'amount' => (float) ($sd['amount'] ?? ($extra['total_price'] ?? 0)),
            ],
            default => [],
        };
    }

    private function mapCanonicalInsuranceOptions(array $gv, array $providerPayload): array
    {
        // Try booking_context.insurance_options first
        $options = $gv['booking_context']['insurance_options']
            ?? $gv['insurance_options']
            ?? [];

        if (!empty($options)) {
            return collect($options)->filter(fn ($ins) => is_array($ins))->map(fn ($ins) => [
                'id' => $ins['id'] ?? '',
                'name' => $ins['name'] ?? '',
                'coverage_type' => $ins['coverage_type'] ?? 'basic',
                'daily_rate' => $ins['daily_rate'] ?? 0,
                'total_price' => $ins['total_price'] ?? 0,
                'currency' => $ins['currency'] ?? 'EUR',
                'excess_amount' => $ins['excess_amount'] ?? null,
                'deposit_amount' => $ins['deposit_amount'] ?? null,
                'included' => $ins['included'] ?? false,
                'description' => $ins['description'] ?? null,
            ])->values()->all();
        }

        // Fallback: build from provider excess/deposit fields
        $result = [];
        $excess = $providerPayload['excess_amount'] ?? null;
        $deposit = $providerPayload['deposit_amount'] ?? null;
        if ($excess || $deposit) {
            $result[] = [
                'id' => 'ins_cdw_basic',
                'name' => 'CDW',
                'coverage_type' => 'basic',
                'daily_rate' => 0,
                'total_price' => 0,
                'currency' => 'EUR',
                'excess_amount' => $excess,
                'deposit_amount' => $deposit,
                'included' => true,
            ];
        }

        // Build FDW from fdw_* fields (Surprice)
        $fdwTotal = (float) ($providerPayload['fdw_total_amount'] ?? 0);
        if ($fdwTotal > 0) {
            $rentalDays = max(1, (int) ($gv['specs']['rental_days'] ?? 1));
            if (isset($gv['pricing']['total_price']) && $totalPrice = (float) $gv['pricing']['total_price']) {
                $dailyBase = $gv['pricing']['price_per_day'] ?? $gv['pricing']['daily_rate'] ?? 0;
                $rentalDays = $dailyBase > 0 ? max(1, (int) round($totalPrice / (float) $dailyBase)) : 1;
            }
            $result[] = [
                'id' => 'ins_surprice_fdw',
                'name' => 'FDW (Full Damage Waiver)',
                'coverage_type' => 'full',
                'daily_rate' => $rentalDays > 0 ? round($fdwTotal / $rentalDays, 2) : $fdwTotal,
                'total_price' => $fdwTotal,
                'currency' => 'EUR',
                'excess_amount' => (float) ($providerPayload['fdw_excess_amount'] ?? 0),
                'deposit_amount' => (float) ($providerPayload['fdw_deposit_amount'] ?? 0),
                'included' => false,
                'description' => 'Full Damage Waiver — reduces excess to zero',
            ];
        }

        return $result;
    }

    private function mapCanonicalExtrasPreview(array $extrasPreview): array
    {
        return collect($extrasPreview)->filter(fn ($extra) => is_array($extra))->map(function ($extra) {
            $dailyRate = (float) ($extra['daily_rate'] ?? 0);
            $totalPrice = (float) ($extra['total_price'] ?? 0);
            $currency = $extra['currency'] ?? 'EUR';
            $id = $extra['id'] ?? '';
            $mandatory = (bool) ($extra['mandatory'] ?? false);

            // Extract raw code from gateway ID (e.g. 'ext_ok_mobility_ADD' → 'ADD')
            $code = $extra['code'] ?? $id;
            if (str_starts_with($code, 'ext_') && substr_count($code, '_') >= 2) {
                $parts = explode('_', $code);
                $code = end($parts);
            }

            return [
                'id' => $id,
                'name' => $extra['name'] ?? '',
                'description' => $extra['description'] ?? ($extra['name'] ?? ''),
                'daily_rate' => $dailyRate,
                'Daily_rate' => $dailyRate,
                'total_for_booking' => $totalPrice,
                'Total_for_this_booking' => $totalPrice,
                'total_for_booking_currency' => $currency,
                'Total_for_this_booking_currency' => $currency,
                'option_id' => $id,
                'optionID' => $id,
                'price' => $dailyRate,
                'amount' => $dailyRate,
                'total_price' => $totalPrice,
                'price_per_day' => $dailyRate,
                'service_id' => $id,
                'currency' => $currency,
                'max_quantity' => $extra['max_quantity'] ?? 1,
                'numberAllowed' => $extra['max_quantity'] ?? 1,
                'mandatory' => $mandatory,
                'required' => $mandatory,
                'type' => $extra['type'] ?? 'equipment',
                'code' => $code,
            ];
        })->values()->all();
    }
}
