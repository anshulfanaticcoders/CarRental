<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Services\GatewaySearchParamsBuilder;
use App\Services\LocationSearchService;
use App\Services\PriceVerificationService;
use App\Services\Search\InternalSearchVehicleFactory;
use App\Services\Vehicles\GatewayVehicleTransformer;
use App\Services\Vehicles\InternalVehicleAvailabilityService;
use App\Services\VrooemGatewayService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VehicleSearchController extends Controller
{
    private const DEFAULT_MARKUP_PERCENT = 15.0;

    private const ONE_WAY_PROVIDERS = [
        'greenmotion',
        'usave',
        'adobe',
        'click2rent',
        'easirent',
        'locauto_rent',
        'recordgo',
        'renteon',
        'surprice',
        'sicily_by_car',
    ];

    public function __construct(
        private LocationSearchService $locationSearchService,
        private GatewaySearchParamsBuilder $paramsBuilder,
        private VrooemGatewayService $gatewayService,
        private GatewayVehicleTransformer $transformer,
        private InternalSearchVehicleFactory $internalFactory,
        private InternalVehicleAvailabilityService $availability,
    ) {}

    private function markupRate(): float
    {
        $raw = config('services.pricing.provider_markup_percent');
        $percent = is_numeric($raw) ? (float) $raw : self::DEFAULT_MARKUP_PERCENT;
        return $percent / 100;
    }

    private function grossUp(float $value, float $rate): float
    {
        return round($value * (1 + $rate), 2);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'unified_location_id' => ['required', 'integer'],
            'dropoff_unified_location_id' => ['nullable', 'integer'],
            'pickup_date' => ['required', 'date_format:Y-m-d'],
            'dropoff_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:pickup_date'],
            'pickup_time' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'dropoff_time' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'driver_age' => ['required', 'integer', 'min:18', 'max:99'],
            'currency' => ['nullable', 'string', 'max:3'],
        ]);

        $start = Carbon::parse("{$validated['pickup_date']} {$validated['pickup_time']}");
        $end = Carbon::parse("{$validated['dropoff_date']} {$validated['dropoff_time']}");
        $rentalDays = max(1, (int) ceil($start->diffInMinutes($end) / 1440));

        if ($end->lessThanOrEqualTo($start)) {
            return response()->json([
                'message' => 'Drop-off time must be after pickup time.',
                'errors' => ['dropoff_time' => ['Drop-off must be after pickup.']],
            ], 422);
        }

        $location = $this->locationSearchService->getLocationByUnifiedId((int) $validated['unified_location_id']);
        if (! $location) {
            return response()->json([
                'message' => 'Pickup location not found.',
            ], 404);
        }

        $builderInput = [
            'unified_location_id' => $validated['unified_location_id'],
            'date_from' => $validated['pickup_date'],
            'date_to' => $validated['dropoff_date'],
            'start_time' => $validated['pickup_time'],
            'end_time' => $validated['dropoff_time'],
            'age' => $validated['driver_age'],
            'dropoff_unified_location_id' => $validated['dropoff_unified_location_id'] ?? null,
        ];

        $gatewayParams = $this->paramsBuilder->build($builderInput);

        try {
            $gatewayResult = $this->gatewayService->searchVehicles($gatewayParams);
        } catch (\Throwable $e) {
            Log::warning('Mobile vehicle search: gateway request failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Search service is temporarily unavailable. Try again shortly.',
            ], 503);
        }

        $dropoffUnifiedId = $validated['dropoff_unified_location_id'] ?? null;
        $isOneWay = ! empty($dropoffUnifiedId)
            && (string) $dropoffUnifiedId !== (string) $validated['unified_location_id'];

        $rawVehicles = $gatewayResult['vehicles'] ?? [];
        $transformed = [];
        foreach ($rawVehicles as $gv) {
            try {
                $tv = $this->transformer->transform($gv, $rentalDays);
                if ($isOneWay) {
                    $src = strtolower(trim((string) ($tv['source'] ?? '')));
                    if (! in_array($src, self::ONE_WAY_PROVIDERS, true)) {
                        continue;
                    }
                }
                $transformed[] = $tv;
            } catch (\Throwable $e) {
                Log::warning('Mobile vehicle search: transform failed', [
                    'vehicle_id' => $gv['id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Internal vendor vehicles do not support one-way rentals — match web behavior.
        $internalTransformed = $isOneWay
            ? []
            : $this->fetchInternalVehicles($location, $validated, $rentalDays);
        $combined = array_merge($internalTransformed, $transformed);

        $markup = $this->markupRate();
        $gatewaySearchId = $gatewayResult['search_id'] ?? null;
        $vehicles = array_values(array_filter(array_map(
            fn ($v) => $this->slimVehicle($v, $markup, $rentalDays, $gatewaySearchId),
            $combined,
        )));

        $providerStatus = $this->mapProviderStatus($gatewayResult);
        if (! $isOneWay) {
            $providerStatus[] = [
                'provider' => 'internal',
                'status' => 'ok',
                'vehicles' => count($internalTransformed),
                'response_time_ms' => null,
                'error' => null,
            ];
        }

        // Seed PriceVerificationService cache so checkout can verify against stored prices.
        // Mirrors GatewaySearchService::search() lines 196-260: stamp price_hash onto each
        // slim vehicle so mobile can pass it back to verifyPrices at checkout time.
        $searchSessionId = 'mobile_' . ($request->user()?->id ?? 'guest') . '_' . now()->timestamp;
        $priceMap = [];
        try {
            $priceMap = app(PriceVerificationService::class)->storeOriginalPrices($searchSessionId, $combined);
        } catch (\Throwable $e) {
            Log::warning('Mobile search: failed to seed price verification cache', [
                'error' => $e->getMessage(),
            ]);
        }

        foreach ($vehicles as &$slim) {
            $id = $slim['id'] ?? null;
            if ($id !== null && isset($priceMap[$id])) {
                $slim['price_hash'] = $priceMap[$id]['price_hash'];
                $slim['vehicle_id_hash'] = $priceMap[$id]['vehicle_id_hash'] ?? null;
            }
        }
        unset($slim);

        return response()->json([
            'vehicles' => $vehicles,
            'provider_status' => $providerStatus,
            'search_session_id' => $searchSessionId,
            'meta' => [
                'total' => count($vehicles),
                'location_name' => $location['name'] ?? null,
                'rental_days' => $rentalDays,
                'gateway_search_id' => $gatewaySearchId,
                'provider_markup_percent' => $markup * 100,
            ],
        ]);
    }

    private function slimVehicle(array $v, float $markup, int $rentalDays, ?string $gatewaySearchId = null): ?array
    {
        // Internal vehicles use nested pricing/specs/policies; gateway uses flat keys.
        $pricing = is_array($v['pricing'] ?? null) ? $v['pricing'] : [];
        $specs = is_array($v['specs'] ?? null) ? $v['specs'] : [];
        $policies = is_array($v['policies'] ?? null) ? $v['policies'] : [];

        $total = $v['total_price'] ?? ($pricing['total_price'] ?? null);
        if (! is_numeric($total) || (float) $total <= 0) {
            return null;
        }

        $totalNet = (float) $total;
        $dailyNet = isset($v['daily_rate'])
            ? (float) $v['daily_rate']
            : (isset($pricing['price_per_day']) ? (float) $pricing['price_per_day'] : ($totalNet / max(1, $rentalDays)));
        $totalGross = $this->grossUp($totalNet, $markup);
        $dailyGross = $this->grossUp($dailyNet, $markup);

        // Security deposit is held + refunded at pickup — kept RAW (matches web's formatPrice usage, no markup)
        $rawDeposit = $v['security_deposit'] ?? ($pricing['deposit_amount'] ?? null);
        $deposit = is_numeric($rawDeposit) ? round((float) $rawDeposit, 2) : null;

        $currency = $v['currency'] ?? ($pricing['currency'] ?? 'EUR');
        $isInternal = ($v['source'] ?? null) === 'internal';
        $pickupLocation = is_array($v['location']['pickup'] ?? null) ? $v['location']['pickup'] : [];
        $providerPayload = is_array($v['booking_context']['provider_payload'] ?? null)
            ? $v['booking_context']['provider_payload']
            : [];

        // Parse features (may be JSON string or array)
        $rawFeatures = $v['features'] ?? ($providerPayload['features'] ?? []);
        if (is_string($rawFeatures)) {
            $decoded = json_decode($rawFeatures, true);
            $rawFeatures = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($rawFeatures)) $rawFeatures = [];

        $paymentMethods = $providerPayload['payment_method'] ?? null;
        if (is_string($paymentMethods)) {
            $decoded = json_decode($paymentMethods, true);
            $paymentMethods = is_array($decoded) ? $decoded : [$paymentMethods];
        }
        if (! is_array($paymentMethods)) $paymentMethods = [];

        return [
            'id' => $v['id'] ?? null,
            'source' => $v['source'] ?? 'unknown',
            'brand' => $v['brand'] ?? null,
            'model' => $v['model'] ?? null,
            'category' => $v['category'] ?? null,
            'image' => $v['image'] ?? null,
            // RAW (net) values exposed under field names PriceVerificationService reads first
            // (see PriceVerificationService.php line 130 + storeOriginalPrices line 40).
            'total' => $totalNet,
            'price_per_day' => $dailyNet,
            'total_price' => $totalGross,
            'total_price_net' => $totalNet,
            'daily_rate' => $dailyGross,
            'daily_rate_net' => $dailyNet,
            'currency' => $currency,
            'transmission' => $v['transmission'] ?? ($specs['transmission'] ?? null),
            'fuel' => $v['fuel'] ?? ($specs['fuel'] ?? null),
            'seating_capacity' => $v['seating_capacity'] ?? ($specs['seating_capacity'] ?? null),
            'doors' => $v['doors'] ?? ($specs['doors'] ?? null),
            'bags' => $v['bags'] ?? ($specs['luggage_large'] ?? null),
            'air_conditioning' => $v['airConditioning'] ?? ($specs['air_conditioning'] ?? null),
            'mileage' => $v['mileage'] ?? ($policies['mileage_policy'] ?? null),
            'sipp_code' => $v['sipp_code'] ?? ($specs['sipp_code'] ?? null),
            'features' => $rawFeatures,
            'security_deposit' => $deposit,
            'plans' => $this->slimPlans($v['products'] ?? [], $markup, $rentalDays, $currency),
            'extras' => $this->slimExtras($v['extras'] ?? ($v['extras_preview'] ?? []), $markup, $rentalDays),
            'protections' => $this->slimExtras($v['protections'] ?? [], $markup, $rentalDays),
            'fuel_policy' => $v['fuel_policy'] ?? ($policies['fuel_policy'] ?? null),
            'cancellation_policy' => is_array($v['cancellation'] ?? null)
                ? ($v['cancellation']['policy_text'] ?? null)
                : (is_array($policies['cancellation'] ?? null) ? ($policies['cancellation']['policy_text'] ?? null) : null),
            'pickup_location_name' => $v['location_details']['name']
                ?? $v['full_vehicle_address']
                ?? ($pickupLocation['name'] ?? ($pickupLocation['full_address'] ?? null)),
            'pickup_address' => $v['pickup_address'] ?? ($pickupLocation['address'] ?? null),
            'pickup_latitude' => is_numeric($v['latitude'] ?? null) && (float) $v['latitude'] !== 0.0
                ? (float) $v['latitude']
                : (is_numeric($pickupLocation['latitude'] ?? null) ? (float) $pickupLocation['latitude'] : null),
            'pickup_longitude' => is_numeric($v['longitude'] ?? null) && (float) $v['longitude'] !== 0.0
                ? (float) $v['longitude']
                : (is_numeric($pickupLocation['longitude'] ?? null) ? (float) $pickupLocation['longitude'] : null),
            'pickup_phone' => $v['location_details']['telephone']
                ?? ($providerPayload['location_phone'] ?? null),
            'pickup_email' => $v['location_details']['email'] ?? null,
            'pickup_office_hours' => $v['location_details']['opening_hours'] ?? null,
            'pickup_instructions' => $v['pickup_instructions']
                ?? ($providerPayload['pickup_instructions'] ?? null),
            'dropoff_address' => $v['dropoff_address']
                ?? ($providerPayload['full_vehicle_address'] ?? null),
            'dropoff_instructions' => $v['dropoff_instructions']
                ?? ($providerPayload['dropoff_instructions'] ?? null),
            'guidelines' => $providerPayload['guidelines'] ?? null,
            'rental_policy' => $providerPayload['rental_policy'] ?? null,
            'terms_policy' => $providerPayload['terms_policy'] ?? null,
            'payment_methods' => array_values(array_filter(array_map('strval', $paymentMethods))),
            'color' => $providerPayload['color'] ?? null,
            'body_style' => $providerPayload['body_style'] ?? null,
            'images' => $this->extractImages($v, $isInternal),
            'included_items' => $this->extractIncludedItems($v, $policies),
            'driver_requirements' => $this->extractDriverRequirements($v),
            'terms' => $this->extractTerms($v),
            'excess_amount' => ($v['benefits']['excess_amount'] ?? null)
                ?? ($v['booking_context']['provider_payload']['benefits']['excess_amount'] ?? null)
                ?? ($pricing['excess_amount'] ?? null),
            'excess_theft_amount' => ($v['benefits']['excess_theft_amount'] ?? null)
                ?? ($v['booking_context']['provider_payload']['benefits']['excess_theft_amount'] ?? null)
                ?? ($pricing['excess_theft_amount'] ?? null),
            'deposit_payment_methods' => $this->parseList(
                ($v['benefits']['deposit_payment_method'] ?? null)
                ?? ($v['booking_context']['provider_payload']['benefits']['deposit_payment_method'] ?? null)
            ),
            'minimum_driver_age' => ($v['benefits']['minimum_driver_age'] ?? null)
                ?? ($v['booking_context']['provider_payload']['benefits']['minimum_driver_age'] ?? null)
                ?? ($policies['minimum_driver_age'] ?? null)
                ?? ($v['driver_requirements']['minimum_driver_age'] ?? null),
            'at_airport' => (bool) ($v['at_airport'] ?? false),
            'provider_code' => $v['provider_code'] ?? ($isInternal ? 'Vrooem' : null),
            'gateway_vehicle_id' => $v['gateway_vehicle_id'] ?? null,
            'provider_markup_percent' => round($markup * 100, 2),
            'office_address' => $v['office_address'] ?? null,
            'office_phone' => $v['office_phone'] ?? null,
            'office_schedule' => $v['office_schedule'] ?? null,
            'protection_plans' => $this->derivePerProviderProtectionPlans($v, $markup, $rentalDays, $currency),
            'tax_breakdown' => $this->deriveTaxBreakdown($v, $totalNet),
            'included_services' => $this->deriveIncludedServices($v),
            'driver_policy' => $this->deriveDriverPolicy($v),
            'cancellation_summary' => $this->deriveCancellationSummary($v),
            'cover_codes' => $this->deriveCoverCodes($v),
            'mandatory_amount' => $this->deriveMandatoryAmount($v, $markup),
            'mandatory_amount_net' => $this->deriveMandatoryAmountNet($v),
            'highlights' => $this->deriveHighlights($v, $providerPayload, $rawBenefits ?? []),
            // Web-parity fields used by StripeCheckoutController validation + provider_metadata persistence.
            'quoteid' => $v['quoteid'] ?? ($v['quote_id'] ?? null),
            'rentalCode' => $v['rentalCode'] ?? ($v['rental_code'] ?? null),
            'gateway_search_id' => $v['gateway_search_id'] ?? $gatewaySearchId,
            'provider_markup_rate' => round($markup, 4),
            'pickup_location_details' => is_array($v['location_details'] ?? null) ? $v['location_details'] : (is_array($pickupLocation) && ! empty($pickupLocation) ? $pickupLocation : null),
            'dropoff_location_details' => is_array($v['dropoff_location_details'] ?? null) ? $v['dropoff_location_details'] : null,
            'protection_code' => $this->deriveDefaultProtectionCode($v),
        ];
    }

    /**
     * Default protection code per provider — mirrors web's adapter defaults so checkout can
     * pass the right `protection_code` without forcing the user to pick.
     */
    private function deriveDefaultProtectionCode(array $v): ?string
    {
        $source = strtolower((string) ($v['source'] ?? ''));
        if ($source === 'adobe') return 'PLI';
        if ($source === 'locauto_rent') return null; // user selects from 7 codes
        // Default: first product/plan code if any.
        $products = is_array($v['products'] ?? null) ? $v['products'] : [];
        foreach ($products as $p) {
            if (is_array($p) && ! empty($p['code'])) return (string) $p['code'];
            if (is_array($p) && ! empty($p['type'])) return (string) $p['type'];
        }
        return null;
    }

    private function derivePerProviderProtectionPlans(array $v, float $markup, int $rentalDays, string $currency): array
    {
        $source = strtolower((string) ($v['source'] ?? ''));

        if ($source === 'adobe') {
            // Adobe: PLI/LDW/SPP live in `protections` array (built from insurance_options).
            // Each has total_price (booking total) + daily_rate. PLI is mandatory.
            $raw = is_array($v['protections'] ?? null) ? $v['protections'] : [];
            $out = $this->slimExtras($raw, $markup, $rentalDays);
            foreach ($out as &$plan) {
                $code = strtoupper((string) ($plan['code'] ?? ''));
                if ($code === 'PLI') {
                    $plan['mandatory'] = true;
                    if (empty($plan['description'])) {
                        $plan['description'] = 'Mandatory liability cover required by law.';
                    }
                }
            }
            return $out;
        }

        if ($source === 'locauto_rent') {
            $codes = ['136', '147', '145', '140', '146', '6', '43'];
            $names = [
                '147' => 'Smart Cover',
                '136' => "Don't Worry",
                '145' => 'Tyres & Glass Protection',
                '140' => 'Theft Protection',
                '146' => 'Roadside Assistance Plus',
                '6'   => 'Premium Cover',
                '43'  => 'Personal Accident',
            ];
            $extras = is_array($v['extras'] ?? null) ? $v['extras'] : [];
            $filtered = array_values(array_filter($extras, function ($e) use ($codes) {
                return is_array($e) && in_array((string) ($e['code'] ?? ''), $codes, true);
            }));
            $out = $this->slimExtras($filtered, $markup, $rentalDays);
            foreach ($out as &$plan) {
                $code = (string) ($plan['code'] ?? '');
                if (isset($names[$code])) $plan['name'] = $names[$code];
            }
            return $out;
        }

        if ($source === 'renteon') {
            // Renteon: insurance_options has daily_rate + total_price — use slimExtras
            $insurance = is_array($v['insurance_options'] ?? null) ? $v['insurance_options'] : [];
            return $this->slimExtras($insurance, $markup, $rentalDays);
        }

        // Generic: pass-through any provider-supplied protections
        return $this->slimExtras($v['protections'] ?? [], $markup, $rentalDays);
    }

    private function deriveTaxBreakdown(array $v, float $totalNet): ?array
    {
        $source = strtolower((string) ($v['source'] ?? ''));
        $supplierData = is_array($v['supplier_data'] ?? null) ? $v['supplier_data'] : [];

        if ($source === 'renteon') {
            $net = $v['provider_net_amount'] ?? ($supplierData['provider_net_amount'] ?? ($supplierData['net_amount'] ?? null));
            $vat = $v['provider_vat_amount'] ?? ($supplierData['provider_vat_amount'] ?? ($supplierData['vat_amount'] ?? null));
            $gross = $v['provider_gross_amount'] ?? ($supplierData['provider_gross_amount'] ?? null);
            if (is_numeric($net) || is_numeric($vat) || is_numeric($gross)) {
                return [
                    'type' => 'renteon',
                    'net' => is_numeric($net) ? round((float) $net, 2) : null,
                    'vat' => is_numeric($vat) ? round((float) $vat, 2) : null,
                    'gross' => is_numeric($gross) ? round((float) $gross, 2) : null,
                    'rate' => null,
                ];
            }
        }

        if ($source === 'okmobility' || $source === 'ok_mobility') {
            $base = $supplierData['base'] ?? ($v['value_without_tax'] ?? null);
            $tax = $supplierData['tax'] ?? ($v['tax_value'] ?? null);
            $total = $supplierData['total'] ?? null;
            $rate = $supplierData['rate'] ?? ($v['tax_rate'] ?? null);
            if (is_numeric($base) || is_numeric($tax) || is_numeric($total)) {
                return [
                    'type' => 'okmobility',
                    'net' => is_numeric($base) ? round((float) $base, 2) : null,
                    'vat' => is_numeric($tax) ? round((float) $tax, 2) : null,
                    'gross' => is_numeric($total) ? round((float) $total, 2) : null,
                    'rate' => is_numeric($rate) ? round((float) $rate, 2) : null,
                ];
            }
        }

        $valueWithoutTax = $v['value_without_tax'] ?? ($supplierData['value_without_tax'] ?? null);
        $taxRate = $v['tax_rate'] ?? ($supplierData['tax_rate'] ?? null);
        if (is_numeric($valueWithoutTax) && is_numeric($taxRate)) {
            $net = (float) $valueWithoutTax;
            $rate = (float) $taxRate;
            $vat = round($net * $rate / 100, 2);
            return [
                'type' => $source,
                'net' => round($net, 2),
                'vat' => $vat,
                'gross' => round($net + $vat, 2),
                'rate' => round($rate, 2),
            ];
        }

        return null;
    }

    private function deriveIncludedServices(array $v): array
    {
        $source = strtolower((string) ($v['source'] ?? ''));
        $supplierData = is_array($v['supplier_data'] ?? null) ? $v['supplier_data'] : [];
        $out = [];

        if ($source === 'renteon') {
            $services = $supplierData['included_services'] ?? ($v['included_services'] ?? []);
            foreach ($services as $s) {
                if (! is_array($s)) continue;
                $out[] = [
                    'name' => (string) ($s['name'] ?? $s['service_name'] ?? 'Service'),
                    'quantity_included' => isset($s['quantity_included']) ? (int) $s['quantity_included'] : null,
                    'description' => $s['description'] ?? null,
                    'excess' => isset($s['excess']) && is_numeric($s['excess']) ? round((float) $s['excess'], 2) : null,
                ];
            }
        }

        if ($source === 'sicily_by_car') {
            $services = $supplierData['included_services'] ?? [];
            foreach ($services as $s) {
                if (! is_array($s)) continue;
                $out[] = [
                    'name' => (string) ($s['name'] ?? 'Service'),
                    'quantity_included' => null,
                    'description' => $s['description'] ?? null,
                    'excess' => isset($s['excess']) && is_numeric($s['excess']) ? round((float) $s['excess'], 2) : null,
                ];
            }
        }

        return $out;
    }

    private function deriveDriverPolicy(array $v): ?array
    {
        $source = strtolower((string) ($v['source'] ?? ''));
        $supplierData = is_array($v['supplier_data'] ?? null) ? $v['supplier_data'] : [];

        if ($source !== 'renteon') return null;

        $dp = $supplierData['driver_policy'] ?? null;
        if (! is_array($dp)) return null;

        return [
            'min_age' => isset($dp['min_age']) ? (int) $dp['min_age'] : null,
            'max_age' => isset($dp['max_age']) ? (int) $dp['max_age'] : null,
            'young_from' => isset($dp['young_from']) ? (int) $dp['young_from'] : null,
            'young_to' => isset($dp['young_to']) ? (int) $dp['young_to'] : null,
            'young_surcharge' => isset($dp['young_surcharge']) && is_numeric($dp['young_surcharge']) ? round((float) $dp['young_surcharge'], 2) : null,
            'senior_from' => isset($dp['senior_from']) ? (int) $dp['senior_from'] : null,
            'senior_to' => isset($dp['senior_to']) ? (int) $dp['senior_to'] : null,
        ];
    }

    private function deriveCancellationSummary(array $v): ?array
    {
        $source = strtolower((string) ($v['source'] ?? ''));
        if ($source !== 'okmobility' && $source !== 'ok_mobility') return null;

        $supplierData = is_array($v['supplier_data'] ?? null) ? $v['supplier_data'] : [];
        $c = $supplierData['cancellation'] ?? ($v['cancellation'] ?? null);
        if (! is_array($c)) return null;

        return [
            'available' => (bool) ($c['available'] ?? false),
            'amount' => isset($c['amount']) && is_numeric($c['amount']) ? round((float) $c['amount'], 2) : null,
            'deadline' => $c['deadline'] ?? null,
        ];
    }

    private function deriveCoverCodes(array $v): array
    {
        $source = strtolower((string) ($v['source'] ?? ''));
        if ($source === 'okmobility' || $source === 'ok_mobility') {
            return ['OPC', 'OPCO'];
        }
        return [];
    }

    private function deriveMandatoryAmount(array $v, float $markup): float
    {
        $source = strtolower((string) ($v['source'] ?? ''));
        if ($source !== 'adobe') return 0.0;
        $pli = $v['pli'] ?? null;
        return is_numeric($pli) ? $this->grossUp((float) $pli, $markup) : 0.0;
    }

    private function deriveMandatoryAmountNet(array $v): float
    {
        $source = strtolower((string) ($v['source'] ?? ''));
        if ($source !== 'adobe') return 0.0;
        $pli = $v['pli'] ?? null;
        return is_numeric($pli) ? round((float) $pli, 2) : 0.0;
    }

    private function deriveHighlights(array $v, array $providerPayload, array $rawBenefits): ?array
    {
        $source = strtolower((string) ($v['source'] ?? ''));
        $supplierData = is_array($v['supplier_data'] ?? null) ? $v['supplier_data'] : [];

        if ($source === 'okmobility' || $source === 'ok_mobility') {
            return [
                'kind' => 'okmobility',
                'payload' => [
                    'fuel_policy' => $v['fuel_policy'] ?? null,
                    'pickup_station_name' => $supplierData['pickup_station_name'] ?? null,
                    'dropoff_station_name' => $supplierData['dropoff_station_name'] ?? null,
                    'extras_included' => $this->splitCsv($supplierData['extras_included'] ?? ''),
                    'extras_required' => $this->splitCsv($supplierData['extras_required'] ?? ''),
                    'extras_available' => $this->splitCsv($supplierData['extras_available'] ?? ''),
                ],
            ];
        }

        if ($source === 'renteon') {
            return [
                'kind' => 'renteon',
                'payload' => [
                    'pickup_office' => $supplierData['pickup_office'] ?? null,
                    'dropoff_office' => $supplierData['dropoff_office'] ?? null,
                    'included_services_count' => is_array($supplierData['included_services'] ?? null) ? count($supplierData['included_services']) : 0,
                ],
            ];
        }

        if ($source === 'adobe') {
            return [
                'kind' => 'adobe',
                'payload' => [
                    'office_address' => $v['office_address'] ?? null,
                    'office_phone' => $v['office_phone'] ?? null,
                    'office_schedule' => $v['office_schedule'] ?? null,
                    'at_airport' => (bool) ($v['at_airport'] ?? false),
                ],
            ];
        }

        if ($source === 'locauto_rent') {
            return [
                'kind' => 'locauto',
                'payload' => [
                    'terms_url' => 'https://portale.locautorent.com/doc/GeneralRentalConditions.pdf',
                ],
            ];
        }

        if ($source === 'sicily_by_car') {
            return ['kind' => 'sicily', 'payload' => []];
        }

        if ($source === 'recordgo') {
            $included = is_array($v['recordgo_products'] ?? null) ? $v['recordgo_products'] : [];
            return [
                'kind' => 'recordgo',
                'payload' => ['products' => $included],
            ];
        }

        if (in_array($source, ['favrica', 'xdrive', 'emr'], true)) {
            return ['kind' => 'additional_services', 'payload' => []];
        }

        if ($source === 'internal') {
            return [
                'kind' => 'internal',
                'payload' => [
                    'has_guidelines' => ! empty($providerPayload['guidelines']),
                    'has_rental_policy' => ! empty($providerPayload['rental_policy']),
                ],
            ];
        }

        return null;
    }

    private function splitCsv(mixed $val): array
    {
        if (is_array($val)) return array_values(array_filter(array_map(fn ($s) => is_string($s) ? trim($s) : null, $val)));
        if (! is_string($val) || trim($val) === '') return [];
        return array_values(array_filter(array_map('trim', explode(',', $val))));
    }

    private function extractImages(array $v, bool $isInternal): array
    {
        if ($isInternal) {
            $host = rtrim(request()->getSchemeAndHttpHost(), '/');
            $imgs = $v['booking_context']['provider_payload']['images']
                ?? $v['images']
                ?? [];
            $out = [];
            foreach ($imgs as $img) {
                $url = is_string($img) ? $img : ($img['image_url'] ?? ($img['image_path'] ?? null));
                if (! $url) continue;
                if (! preg_match('#^https?://#i', $url)) {
                    $url = $host.'/storage/'.ltrim($url, '/');
                }
                $out[] = $url;
            }
            return array_values(array_unique($out));
        }
        $imgs = $v['supplier_data']['images'] ?? ($v['images'] ?? []);
        $out = [];
        foreach ($imgs as $img) {
            $url = is_string($img) ? $img : ($img['image_url'] ?? null);
            if ($url) $out[] = $url;
        }
        return array_values(array_unique($out));
    }

    private function extractIncludedItems(array $v, array $policies): array
    {
        $items = [];
        $rawBenefits = is_array($v['booking_context']['provider_payload']['benefits'] ?? null)
            ? $v['booking_context']['provider_payload']['benefits']
            : (is_array($v['benefits'] ?? null) ? $v['benefits'] : []);

        // Mileage line
        $mileage = $v['mileage'] ?? ($policies['mileage_policy'] ?? null);
        if (is_string($mileage) && strtolower($mileage) === 'limited') {
            $perDay = $rawBenefits['limited_km_per_day_range'] ?? null;
            $pricePerKm = $rawBenefits['price_per_km_per_day'] ?? null;
            $line = $perDay ? "Limited mileage: {$perDay} km/day" : 'Limited mileage';
            if (is_numeric($pricePerKm)) {
                $line .= " (extra: {$pricePerKm}/km)";
            }
            $items[] = $line;
        } elseif (is_string($mileage) && strtolower($mileage) === 'unlimited') {
            $items[] = 'Unlimited mileage';
        } elseif ($mileage) {
            $items[] = is_string($mileage) ? "Mileage: {$mileage}" : 'Mileage included';
        }

        // Fuel policy
        $fuel = $v['fuel_policy'] ?? ($policies['fuel_policy'] ?? null);
        if ($fuel) {
            $items[] = 'Fuel policy: '.ucfirst((string) $fuel);
        }

        // Cancellation
        $cancellation = $policies['cancellation'] ?? null;
        if (is_array($cancellation) && ! empty($cancellation['available'])) {
            $days = $cancellation['days_before_pickup'] ?? null;
            $items[] = $days ? "Free cancellation up to {$days} day".($days === 1 ? '' : 's')." before pickup" : 'Free cancellation';
        } elseif (! empty($rawBenefits['cancellation_available_per_day'])) {
            $days = $rawBenefits['cancellation_available_per_day_date'] ?? null;
            $items[] = $days ? "Free cancellation up to {$days} day".((int) $days === 1 ? '' : 's')." before pickup" : 'Free cancellation';
        }

        // Provider-side flags
        if (! empty($rawBenefits['theft_protection'])) $items[] = 'Theft protection';
        if (! empty($rawBenefits['collision_damage_waiver']) || ! empty($rawBenefits['cdw'])) $items[] = 'Collision damage waiver';
        if (! empty($rawBenefits['airport_fee_included'])) $items[] = 'Airport fee included';
        if (! empty($rawBenefits['vat_included'])) $items[] = 'VAT included';
        if (! empty($rawBenefits['additional_driver_included'])) $items[] = 'Additional driver included';

        // Free-form features
        if (is_array($v['features'] ?? null)) {
            foreach ($v['features'] as $f) {
                if (is_string($f) && ! in_array($f, $items, true)) $items[] = $f;
            }
        }

        // Booking confirmation always
        $items[] = '24/7 booking confirmation';

        return array_values(array_unique($items));
    }

    private function extractDriverRequirements(array $v): array
    {
        $req = $v['driver_requirements'] ?? null;
        $labels = [
            'driving_license' => 'Valid driving license',
            'driving_license_required' => 'Valid driving license',
            'passport' => 'Passport / ID',
            'passport_required' => 'Passport / ID',
            'credit_card' => 'Credit card in main driver name',
            'credit_card_required' => 'Credit card in main driver name',
            'age_proof' => 'Age verification document',
            'utility_bill' => 'Utility bill (address proof)',
            'international_license' => 'International driving permit',
        ];

        $out = [];
        if (is_array($req)) {
            foreach ($req as $key => $value) {
                if ($key === 'mileage_type' || $key === 'minimum_driver_age') continue;
                $val = strtolower((string) $value);
                if (in_array($val, ['1', 'true', 'yes', 'y'], true)) {
                    $out[] = $labels[$key] ?? ucwords(str_replace('_', ' ', $key));
                }
            }
        }
        return array_values(array_unique($out));
    }

    private function extractTerms(array $v): array
    {
        $terms = $v['terms'] ?? null;
        if (! is_array($terms)) return [];
        $out = [];
        foreach ($terms as $section) {
            if (! is_array($section)) continue;
            $name = (string) ($section['name'] ?? 'Terms');
            $conditions = $section['conditions'] ?? [];
            if (! is_array($conditions)) $conditions = [$conditions];
            $clean = array_values(array_filter(array_map('strval', $conditions)));
            if ($clean) $out[] = ['name' => $name, 'conditions' => $clean];
        }
        return $out;
    }

    private function parseList(mixed $val): array
    {
        if (! $val) return [];
        if (is_string($val)) {
            $decoded = json_decode($val, true);
            $val = is_array($decoded) ? $decoded : [$val];
        }
        if (! is_array($val)) return [];
        return array_values(array_filter(array_map(fn ($v) => is_string($v) ? $v : null, $val)));
    }

    public function checkAvailability(Request $request, int $vehicleId): JsonResponse
    {
        $validated = $request->validate([
            'pickup_date' => ['required', 'date_format:Y-m-d'],
            'dropoff_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:pickup_date'],
            'pickup_time' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'dropoff_time' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'driver_age' => ['required', 'integer', 'min:18', 'max:99'],
        ]);

        $start = Carbon::parse("{$validated['pickup_date']} {$validated['pickup_time']}");
        $end = Carbon::parse("{$validated['dropoff_date']} {$validated['dropoff_time']}");
        if ($end->lessThanOrEqualTo($start)) {
            return response()->json([
                'available' => false,
                'reason' => 'Drop-off must be after pickup.',
            ], 422);
        }
        $rentalDays = max(1, (int) ceil($start->diffInMinutes($end) / 1440));

        $query = Vehicle::query()
            ->with(['images', 'vendor.profile', 'vendorProfile', 'vendorProfileData', 'benefits', 'vendorPlans', 'addons'])
            ->where('id', $vehicleId);

        $this->availability->apply($query, [
            'pickup_date' => $validated['pickup_date'],
            'pickup_time' => $validated['pickup_time'],
            'dropoff_date' => $validated['dropoff_date'],
            'dropoff_time' => $validated['dropoff_time'],
        ]);

        $vehicle = $query->first();
        if (! $vehicle) {
            // Fallback: load without availability filter to distinguish 404 vs not-available
            $exists = Vehicle::find($vehicleId);
            if (! $exists) {
                return response()->json([
                    'available' => false,
                    'reason' => 'Vehicle not found.',
                ], 404);
            }
            return response()->json([
                'available' => false,
                'reason' => 'Not available for the selected dates.',
            ]);
        }

        try {
            $payload = $vehicle->toArray();
            $payload['images'] = $vehicle->images?->toArray() ?? [];
            $payload['vendorPlans'] = $vehicle->vendorPlans?->toArray() ?? [];
            $payload['vendorProfile'] = $vehicle->vendorProfile?->toArray() ?? [];
            $payload['vendorProfileData'] = $vehicle->vendorProfileData?->toArray() ?? [];
            $payload['benefits'] = $vehicle->benefits?->toArray() ?? [];
            $payload['addons'] = $vehicle->addons?->toArray() ?? [];

            $transformed = $this->internalFactory->make($payload, $rentalDays, [
                'pickup_location_id' => null,
                'dropoff_location_id' => null,
                'pickup_latitude' => null,
                'pickup_longitude' => null,
            ]);

            $markup = $this->markupRate();
            $slim = $this->slimVehicle($transformed, $markup, $rentalDays);

            if (! $slim) {
                return response()->json([
                    'available' => false,
                    'reason' => 'Pricing unavailable for the selected dates.',
                ]);
            }

            return response()->json([
                'available' => true,
                'vehicle' => $slim,
                'meta' => [
                    'rental_days' => $rentalDays,
                    'provider_markup_percent' => $markup * 100,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Mobile availability check: transform failed', [
                'vehicle_id' => $vehicleId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'available' => false,
                'reason' => 'Could not compute live quote. Try again.',
            ], 500);
        }
    }

    private function fetchInternalVehicles(array $location, array $validated, int $rentalDays): array
    {
        try {
            $city = $location['city'] ?? null;
            $country = $location['country'] ?? null;
            $countryCode = $location['country_code'] ?? null;
            $lat = $location['latitude'] ?? null;
            $lng = $location['longitude'] ?? null;

            $query = Vehicle::query()
                ->with(['images', 'vendor.profile', 'vendorProfile', 'vendorProfileData', 'benefits', 'vendorPlans', 'addons']);

            if (! empty($city)) {
                $query->where('city', $city);
            } elseif (! empty($countryCode)) {
                $query->where('country_code', $countryCode);
            } elseif (! empty($country)) {
                $query->where('country', $country);
            } else {
                return [];
            }

            $this->availability->apply($query, [
                'pickup_date' => $validated['pickup_date'],
                'pickup_time' => $validated['pickup_time'],
                'dropoff_date' => $validated['dropoff_date'],
                'dropoff_time' => $validated['dropoff_time'],
            ]);

            $rows = $query->limit(50)->get();
            $context = [
                'pickup_location_id' => $location['unified_location_id'] ?? null,
                'dropoff_location_id' => $validated['dropoff_unified_location_id'] ?? null,
                'pickup_latitude' => $lat,
                'pickup_longitude' => $lng,
            ];

            $out = [];
            foreach ($rows as $vehicle) {
                try {
                    $payload = $vehicle->toArray();
                    $payload['images'] = $vehicle->images?->toArray() ?? [];
                    $payload['vendorPlans'] = $vehicle->vendorPlans?->toArray() ?? [];
                    $payload['vendorProfile'] = $vehicle->vendorProfile?->toArray() ?? [];
                    $payload['vendorProfileData'] = $vehicle->vendorProfileData?->toArray() ?? [];
                    $payload['benefits'] = $vehicle->benefits?->toArray() ?? [];
                    $payload['addons'] = $vehicle->addons?->toArray() ?? [];
                    $out[] = $this->internalFactory->make($payload, $rentalDays, $context);
                } catch (\Throwable $e) {
                    Log::warning('Mobile vehicle search: internal vehicle transform failed', [
                        'vehicle_id' => $vehicle->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            return $out;
        } catch (\Throwable $e) {
            Log::warning('Mobile vehicle search: internal fetch failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function slimPlans(array $products, float $markup, int $rentalDays, string $currency): array
    {
        $out = [];
        foreach ($products as $p) {
            if (! is_array($p)) continue;
            $totalNet = (float) ($p['total'] ?? 0);
            if ($totalNet <= 0) continue;
            $dailyNet = (float) ($p['price_per_day'] ?? ($totalNet / max(1, $rentalDays)));
            $out[] = [
                'code' => (string) ($p['type'] ?? 'BAS'),
                'name' => (string) ($p['name'] ?? 'Basic'),
                'total' => $this->grossUp($totalNet, $markup),
                'total_net' => round($totalNet, 2),
                'daily_rate' => $this->grossUp($dailyNet, $markup),
                'daily_rate_net' => round($dailyNet, 2),
                'currency' => $p['currency'] ?? $currency,
                // Deposit and excess are held/refunded by vendor at pickup — NOT marked up (matches web's formatPrice vs formatRentalPrice rule)
                'excess' => isset($p['excess']) && is_numeric($p['excess']) ? round((float) $p['excess'], 2) : null,
                'deposit' => isset($p['deposit']) && is_numeric($p['deposit']) ? round((float) $p['deposit'], 2) : null,
                'mileage' => $p['mileage'] ?? null,
                'fuel_policy' => $p['fuelpolicy'] ?? null,
                'benefits' => is_array($p['benefits'] ?? null) ? $p['benefits'] : [],
                'is_basic' => strtoupper((string) ($p['type'] ?? 'BAS')) === 'BAS',
            ];
        }
        return $out;
    }

    private function slimExtras(array $extras, float $markup, int $rentalDays = 1): array
    {
        $out = [];
        foreach ($extras as $e) {
            if (! is_array($e)) continue;

            // Prefer total_for_booking / total_price (already × days). Fall back to daily_rate.
            $totalForBooking = $e['total_for_booking'] ?? $e['total_price'] ?? $e['total'] ?? null;
            $dailyRate = $e['daily_rate'] ?? $e['price_per_day'] ?? $e['unit_charge'] ?? null;
            if (! is_numeric($dailyRate) && is_numeric($e['price'] ?? null) && (bool) ($e['per_day'] ?? false)) {
                $dailyRate = $e['price'];
            }

            // Resolve booking-total amount: prefer total_for_booking, else daily × days, else flat price
            if (is_numeric($totalForBooking) && (float) $totalForBooking > 0) {
                $amountForBooking = (float) $totalForBooking;
            } elseif (is_numeric($dailyRate) && (float) $dailyRate > 0) {
                $amountForBooking = (float) $dailyRate * max(1, $rentalDays);
            } elseif (is_numeric($e['amount'] ?? null)) {
                $amountForBooking = (float) $e['amount'];
            } elseif (is_numeric($e['price'] ?? null)) {
                $amountForBooking = (float) $e['price'];
            } else {
                continue;
            }

            $hasDaily = is_numeric($dailyRate) && (float) $dailyRate > 0;
            $pricingType = $e['pricing_type'] ?? ($hasDaily ? 'per_day' : 'per_rental');

            $out[] = [
                'code' => (string) ($e['code'] ?? $e['id'] ?? ''),
                'id' => (string) ($e['id'] ?? $e['code'] ?? ''),
                'name' => (string) ($e['name'] ?? $e['description'] ?? 'Extra'),
                'description' => $e['description'] ?? null,
                'amount' => $this->grossUp($amountForBooking, $markup),
                'amount_net' => round($amountForBooking, 2),
                'daily_rate' => $hasDaily ? $this->grossUp((float) $dailyRate, $markup) : null,
                'daily_rate_net' => $hasDaily ? round((float) $dailyRate, 2) : null,
                'currency' => $e['currency'] ?? null,
                'pricing_type' => $pricingType,
                'max_quantity' => isset($e['max_quantity']) && is_numeric($e['max_quantity']) ? (int) $e['max_quantity'] : 1,
                'mandatory' => (bool) ($e['mandatory'] ?? false),
                'included' => (bool) ($e['included'] ?? false),
            ];
        }
        return $out;
    }

    private function mapProviderStatus(array $gatewayResult): array
    {
        $out = [];
        foreach (($gatewayResult['supplier_results'] ?? []) as $sr) {
            if (! is_array($sr)) continue;
            $out[] = [
                'provider' => $this->transformer->normalizeSupplierId((string) ($sr['supplier_id'] ?? 'unknown')),
                'status' => empty($sr['error']) ? 'ok' : 'error',
                'vehicles' => (int) ($sr['vehicle_count'] ?? 0),
                'response_time_ms' => $sr['response_time_ms'] ?? null,
                'error' => $sr['error'] ?? null,
            ];
        }
        return $out;
    }
}
