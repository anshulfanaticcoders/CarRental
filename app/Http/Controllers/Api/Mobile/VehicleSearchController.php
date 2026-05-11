<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Services\GatewaySearchParamsBuilder;
use App\Services\LocationSearchService;
use App\Services\Vehicles\GatewayVehicleTransformer;
use App\Services\VrooemGatewayService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VehicleSearchController extends Controller
{
    private const DEFAULT_MARKUP_PERCENT = 15.0;

    public function __construct(
        private LocationSearchService $locationSearchService,
        private GatewaySearchParamsBuilder $paramsBuilder,
        private VrooemGatewayService $gatewayService,
        private GatewayVehicleTransformer $transformer,
    ) {}

    private function markupRate(): float
    {
        $raw = env('PROVIDER_MARKUP_PERCENT');
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

        $rawVehicles = $gatewayResult['vehicles'] ?? [];
        $transformed = [];
        foreach ($rawVehicles as $gv) {
            try {
                $transformed[] = $this->transformer->transform($gv, $rentalDays);
            } catch (\Throwable $e) {
                Log::warning('Mobile vehicle search: transform failed', [
                    'vehicle_id' => $gv['id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $markup = $this->markupRate();
        $vehicles = array_values(array_filter(array_map(
            fn ($v) => $this->slimVehicle($v, $markup, $rentalDays),
            $transformed,
        )));

        $providerStatus = $this->mapProviderStatus($gatewayResult);

        return response()->json([
            'vehicles' => $vehicles,
            'provider_status' => $providerStatus,
            'meta' => [
                'total' => count($vehicles),
                'location_name' => $location['name'] ?? null,
                'rental_days' => $rentalDays,
                'gateway_search_id' => $gatewayResult['search_id'] ?? null,
                'provider_markup_percent' => $markup * 100,
            ],
        ]);
    }

    private function slimVehicle(array $v, float $markup, int $rentalDays): ?array
    {
        $total = $v['total_price'] ?? null;
        if (! is_numeric($total) || (float) $total <= 0) {
            return null;
        }

        $totalNet = (float) $total;
        $dailyNet = isset($v['daily_rate']) ? (float) $v['daily_rate'] : ($totalNet / max(1, $rentalDays));
        $totalGross = $this->grossUp($totalNet, $markup);
        $dailyGross = $this->grossUp($dailyNet, $markup);
        $deposit = isset($v['security_deposit']) && is_numeric($v['security_deposit'])
            ? $this->grossUp((float) $v['security_deposit'], $markup)
            : null;

        return [
            'id' => $v['id'] ?? null,
            'source' => $v['source'] ?? 'unknown',
            'brand' => $v['brand'] ?? null,
            'model' => $v['model'] ?? null,
            'category' => $v['category'] ?? null,
            'image' => $v['image'] ?? null,
            'total_price' => $totalGross,
            'total_price_net' => $totalNet,
            'daily_rate' => $dailyGross,
            'daily_rate_net' => $dailyNet,
            'currency' => $v['currency'] ?? 'EUR',
            'transmission' => $v['transmission'] ?? null,
            'fuel' => $v['fuel'] ?? null,
            'seating_capacity' => $v['seating_capacity'] ?? null,
            'doors' => $v['doors'] ?? null,
            'bags' => $v['bags'] ?? null,
            'air_conditioning' => $v['airConditioning'] ?? null,
            'mileage' => $v['mileage'] ?? null,
            'sipp_code' => $v['sipp_code'] ?? null,
            'features' => $v['features'] ?? [],
            'security_deposit' => $deposit,
            'plans' => $this->slimPlans($v['products'] ?? [], $markup, $rentalDays, $v['currency'] ?? 'EUR'),
            'extras' => $this->slimExtras($v['extras'] ?? [], $markup),
            'protections' => $this->slimExtras($v['protections'] ?? [], $markup),
            'fuel_policy' => $v['fuel_policy'] ?? null,
            'cancellation_policy' => is_array($v['cancellation'] ?? null) ? ($v['cancellation']['policy_text'] ?? null) : null,
            'pickup_location_name' => $v['location_details']['name'] ?? $v['full_vehicle_address'] ?? null,
            'pickup_address' => $v['pickup_address'] ?? null,
            'at_airport' => (bool) ($v['at_airport'] ?? false),
            'provider_code' => $v['provider_code'] ?? null,
            'gateway_vehicle_id' => $v['gateway_vehicle_id'] ?? null,
        ];
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
                'daily_rate' => $this->grossUp($dailyNet, $markup),
                'currency' => $p['currency'] ?? $currency,
                'excess' => isset($p['excess']) && is_numeric($p['excess']) ? $this->grossUp((float) $p['excess'], $markup) : null,
                'deposit' => isset($p['deposit']) && is_numeric($p['deposit']) ? $this->grossUp((float) $p['deposit'], $markup) : null,
                'mileage' => $p['mileage'] ?? null,
                'fuel_policy' => $p['fuelpolicy'] ?? null,
                'benefits' => is_array($p['benefits'] ?? null) ? $p['benefits'] : [],
                'is_basic' => strtoupper((string) ($p['type'] ?? 'BAS')) === 'BAS',
            ];
        }
        return $out;
    }

    private function slimExtras(array $extras, float $markup): array
    {
        $out = [];
        foreach ($extras as $e) {
            if (! is_array($e)) continue;
            $amount = $e['amount'] ?? $e['price'] ?? $e['total'] ?? null;
            if (! is_numeric($amount)) continue;
            $out[] = [
                'code' => (string) ($e['code'] ?? $e['id'] ?? ''),
                'name' => (string) ($e['name'] ?? $e['description'] ?? 'Extra'),
                'description' => $e['description'] ?? null,
                'amount' => $this->grossUp((float) $amount, $markup),
                'currency' => $e['currency'] ?? null,
                'pricing_type' => $e['pricing_type'] ?? $e['type'] ?? 'per_rental',
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
