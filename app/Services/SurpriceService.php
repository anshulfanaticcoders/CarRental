<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SurpriceService
{
    private string $baseUrl;
    private string $apiKey;
    private string $rateCode;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.surprice.base_url'), '/');
        $this->apiKey = (string) config('services.surprice.api_key', '');
        $this->rateCode = (string) config('services.surprice.rate_code', 'Vrooem');
        $this->timeout = (int) config('services.surprice.timeout', 30);
    }

    // ─── HTTP ───────────────────────────────────────────────────────────

    private function makeRequest(string $method, string $endpoint, array $data = [], array $query = [])
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');

        try {
            Log::info("Surprice API Request: {$method} {$url}", [
                'data' => $method === 'POST' ? $data : [],
                'query' => $query,
            ]);

            $request = Http::timeout($this->timeout)
                ->withToken($this->apiKey)
                ->acceptJson();

            if (!empty($query)) {
                $url .= '?' . http_build_query($query);
            }

            $response = match (strtoupper($method)) {
                'GET' => $request->get($url),
                'PUT' => $request->put($url, $data),
                'PATCH' => $request->patch($url, $data),
                'DELETE' => $request->delete($url, $data),
                default => $request->post($url, $data),
            };

            if ($response->successful()) {
                Log::info("Surprice API Response OK", [
                    'status' => $response->status(),
                    'url' => $url,
                ]);
                return $response->json();
            }

            Log::error("Surprice API Error: " . $response->status(), [
                'url' => $url,
                'body' => $response->body(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error("Surprice API Exception: " . $e->getMessage(), [
                'url' => $url,
            ]);
            return null;
        }
    }

    // ─── LOCATIONS ──────────────────────────────────────────────────────

    public function getLocations(int $limit = 500): array
    {
        $result = $this->makeRequest('GET', 'v1/location/search', [], ['limit' => $limit]);

        if (!is_array($result) || !isset($result['results'])) {
            return [];
        }

        return $result['results'];
    }

    public function getLocationExtras(string $locationCode, string $extendedLocationCode): array
    {
        $result = $this->makeRequest('GET', 'v1/location/extras', [], [
            'locationCode' => $locationCode,
            'extendedLocationCode' => $extendedLocationCode,
        ]);

        return is_array($result) ? ($result['results'] ?? []) : [];
    }

    public function getLocationInsurances(string $locationCode, string $extendedLocationCode): array
    {
        $result = $this->makeRequest('GET', 'v1/location/insurances', [], [
            'locationCode' => $locationCode,
            'extendedLocationCode' => $extendedLocationCode,
        ]);

        return is_array($result) ? ($result['results'] ?? []) : [];
    }

    // ─── AVAILABILITY ───────────────────────────────────────────────────

    public function searchVehicles(
        string $pickupCode,
        string $pickupExtCode,
        string $dropoffCode,
        string $dropoffExtCode,
        string $pickupDateTime,
        string $dropoffDateTime,
        array $options = []
    ): ?array {
        $data = [
            'pickUpDateTime' => $pickupDateTime,
            'returnDateTime' => $dropoffDateTime,
            'pickUpLocationCode' => $pickupCode,
            'pickUpExtendedLocationCode' => $pickupExtCode,
            'returnLocationCode' => $dropoffCode,
            'returnExtendedLocationCode' => $dropoffExtCode,
            'rateCode' => $this->rateCode,
            'returnExtras' => true,
        ];

        if (!empty($options['driver_age'])) {
            $data['driverAge'] = (int) $options['driver_age'];
        }

        return $this->makeRequest('POST', 'v1/availability', $data);
    }

    /**
     * Search and transform vehicles into our unified format.
     */
    public function getTransformedVehicles(
        string $pickupCode,
        string $pickupExtCode,
        string $dropoffCode,
        string $dropoffExtCode,
        string $pickupDateTime,
        string $dropoffDateTime,
        array $options = [],
        ?float $locationLat = null,
        ?float $locationLng = null,
        ?string $locationName = null,
        int $rentalDays = 1
    ): array {
        $response = $this->searchVehicles(
            $pickupCode,
            $pickupExtCode,
            $dropoffCode,
            $dropoffExtCode,
            $pickupDateTime,
            $dropoffDateTime,
            $options
        );

        if (!is_array($response) || empty($response['productOfferings'])) {
            return [];
        }

        $pickupStation = $response['pickupStationInfo'] ?? [];
        $returnStation = $response['returnStationInfo'] ?? [];

        // Use station coordinates if caller didn't provide them
        if ($locationLat === null || $locationLng === null) {
            $coords = $pickupStation['address']['coordinates'] ?? [];
            $locationLat = (float) ($coords['lat'] ?? $coords['latitude'] ?? 0);
            $locationLng = (float) ($coords['lon'] ?? $coords['longitude'] ?? 0);
        }

        if ($locationName === null) {
            $locationName = $pickupStation['name'] ?? 'Surprice Location';
        }

        $vehicles = [];
        foreach ($response['productOfferings'] as $offering) {
            $transformed = $this->transformVehicle(
                $offering,
                $pickupCode,
                $pickupExtCode,
                $dropoffCode,
                $dropoffExtCode,
                $locationLat,
                $locationLng,
                $locationName,
                $rentalDays,
                $pickupStation,
                $returnStation
            );
            if ($transformed !== null) {
                $vehicles[] = $transformed;
            }
        }

        Log::info('Surprice vehicles transformed', ['count' => count($vehicles)]);
        return $vehicles;
    }

    private function transformVehicle(
        array $offering,
        string $pickupCode,
        string $pickupExtCode,
        string $dropoffCode,
        string $dropoffExtCode,
        float $locationLat,
        float $locationLng,
        string $locationName,
        int $rentalDays,
        array $pickupStation = [],
        array $returnStation = []
    ): ?array {
        $vehicle = $offering['vehicle'] ?? [];
        $rentalDetails = $offering['rentalDetails'] ?? [];

        if (empty($vehicle) || empty($rentalDetails)) {
            return null;
        }

        // Use first rental detail (our Vrooem rate)
        $detail = $rentalDetails[0];
        $rate = $detail['rentalRate'] ?? [];
        $totalCharge = $detail['totalCharge'] ?? [];
        $qualifier = $rate['rateQualifier'] ?? [];

        $sippCode = $vehicle['code'] ?? null;
        $description = $vehicle['description'] ?? 'Surprice Vehicle';
        [$brand, $model] = $this->splitDescription($description);

        $totalAmount = (float) ($totalCharge['estimatedTotalAmount'] ?? 0);
        $currency = $totalCharge['currencyCode'] ?? 'EUR';
        $pricePerDay = $rentalDays > 0 ? $totalAmount / $rentalDays : $totalAmount;

        $vendorRateId = $qualifier['vendorRateID'] ?? '';
        $rateCode = $qualifier['rateCode'] ?? $this->rateCode;

        // Mileage policy
        $mileagePolicy = $rate['mileagePolicy'] ?? [];
        $mileageUnlimited = $mileagePolicy['unlimited'] ?? false;
        $mileageText = $mileageUnlimited
            ? 'Unlimited'
            : (($mileagePolicy['quantity'] ?? 0) . ' ' . ($mileagePolicy['distUnitName'] ?? 'km') . '/' . ($mileagePolicy['vehiclePeriodUnitName'] ?? 'day'));

        // Map extras from rate
        $extras = $this->mapExtras($rate['extras'] ?? []);

        // Map surcharges from vehicleCharges (non-rate items)
        $surcharges = $this->mapSurcharges($rate['vehicleCharges'] ?? []);

        // Insurance info
        $insurance = $rate['insurance'] ?? [];

        // Station info
        $pickupAdditionalInfo = $pickupStation['additionalInfo'] ?? [];
        $returnAdditionalInfo = $returnStation['additionalInfo'] ?? [];
        $pickupAddress = $pickupStation['address'] ?? [];
        $returnAddress = $returnStation['address'] ?? [];

        // Use exact coordinates from availability response (more accurate than location search)
        $pickupCoords = $pickupAddress['coordinates'] ?? [];
        $stationLat = (float) ($pickupCoords['latitude'] ?? $pickupCoords['lat'] ?? $locationLat);
        $stationLng = (float) ($pickupCoords['longitude'] ?? $pickupCoords['lon'] ?? $locationLng);

        return [
            'id' => 'surprice_' . $pickupCode . '_' . $sippCode . '_' . $vendorRateId,
            'source' => 'surprice',
            'provider_code' => 'surprice',
            'brand' => $brand,
            'model' => $model,
            'category' => $vehicle['vehMakeModel'] ?? $description,
            'sipp_code' => $sippCode,
            'image' => $vehicle['pictureURL'] ?? '/images/dummyCarImaage.png',
            'price_per_day' => round($pricePerDay, 2),
            'total_price' => round($totalAmount, 2),
            'price_per_week' => round($pricePerDay * 7, 2),
            'price_per_month' => round($pricePerDay * 30, 2),
            'currency' => $currency,
            'transmission' => $this->normalizeTransmission($vehicle['transmissionType'] ?? 'Manual'),
            'fuel' => $this->normalizeFuel($sippCode),
            'seating_capacity' => (int) ($vehicle['passengerQuantity'] ?? 4),
            'doors' => (int) ($vehicle['doorsNum'] ?? 4),
            'luggage' => $vehicle['suitcasesNum'] ?? null,
            'mileage' => $mileageText,
            'airConditioning' => (bool) ($vehicle['airConditionInd'] ?? true),
            'latitude' => $stationLat,
            'longitude' => $stationLng,
            'full_vehicle_address' => ($pickupAddress['addressLine'][0] ?? null) ?: $locationName,
            'provider_pickup_id' => $pickupCode,
            'provider_return_id' => $dropoffCode,
            'availability' => true,

            // Location details (matches BookingExtrasStep.vue expected format)
            'location_details' => [
                'name' => $pickupStation['name'] ?? $locationName,
                'station_type' => $pickupStation['stationType'] ?? null,
                'is_meet_and_greet' => $pickupStation['isMeetAndGreet'] ?? false,
                'address_1' => $pickupAddress['addressLine'][0] ?? null,
                'address_city' => $pickupAddress['city'] ?? null,
                'address_postcode' => $pickupAddress['postalCode'] ?? null,
                'address_country' => ($pickupAddress['country']['name'] ?? null),
                'telephone' => $pickupStation['telephone'] ?? null,
                'opening_hours' => $this->formatOperationTime($pickupAdditionalInfo['operationTime'] ?? []),
                'collection_details' => $pickupAdditionalInfo['text'] ?? null,
            ],
            'dropoff_location_details' => [
                'name' => $returnStation['name'] ?? $locationName,
                'station_type' => $returnStation['stationType'] ?? null,
                'address_1' => $returnAddress['addressLine'][0] ?? null,
                'address_city' => $returnAddress['city'] ?? null,
                'address_postcode' => $returnAddress['postalCode'] ?? null,
                'address_country' => ($returnAddress['country']['name'] ?? null),
                'telephone' => $returnStation['telephone'] ?? null,
                'opening_hours' => $this->formatOperationTime($returnAdditionalInfo['operationTime'] ?? []),
                'collection_details' => $returnAdditionalInfo['text'] ?? null,
            ],

            'benefits' => [
                'minimum_driver_age' => (int) ($vehicle['minDriverAge'] ?? 21),
                'maximum_driver_age' => (int) ($vehicle['maxDriverAge'] ?? 99),
                'young_driver_age_from' => 18,
                'young_driver_age_to' => (int) ($vehicle['youngDriverAge'] ?? 25),
                'old_driver_age' => (int) ($vehicle['oldDriverAge'] ?? 75),
                'deposit_amount' => (float) ($vehicle['insuranceDeposit'] ?? 0),
                'deposit_currency' => $currency,
                'excess_amount' => (float) ($vehicle['insuranceExcess'] ?? 0),
                'theft_excess' => (float) ($vehicle['theftExcess'] ?? 0),
                'fuel_policy' => 'Full to Full',
                'mileage_policy' => $mileageText,
                'pay_on_arrival' => true,
                'insurance_type' => $insurance['description'] ?? 'CDW',
                'insurance_description' => $insurance['detailedDescription'] ?? null,
            ],
            'extras' => $extras,
            'surcharges' => $surcharges,
            'products' => [
                [
                    'type' => 'BAS',
                    'total' => (string) round($totalAmount, 2),
                    'currency' => $currency,
                    'deposit' => (string) ($vehicle['insuranceDeposit'] ?? 0),
                    'excess' => (string) ($vehicle['insuranceExcess'] ?? 0),
                    'payment_type' => 'POA',
                    'mileage' => $mileageText,
                    'fuelpolicy' => 'FF',
                    'insurance_type' => $insurance['description'] ?? 'CDW',
                    'insurance_description' => $insurance['detailedDescription'] ?? null,
                ],
            ],
            'insurance_options' => [],
            'features' => $this->buildFeatures($vehicle),
            'options' => [],
            // Surprice-specific metadata for booking
            'surprice_vendor_rate_id' => $vendorRateId,
            'surprice_rate_code' => $rateCode,
            'surprice_extended_pickup_code' => $pickupExtCode,
            'surprice_extended_dropoff_code' => $dropoffExtCode,
            'surprice_pickup_info' => $pickupAdditionalInfo['text'] ?? null,
            'surprice_operation_time' => $pickupAdditionalInfo['operationTime'] ?? null,
            'surprice_vat_amount' => (float) ($totalCharge['VAT'] ?? 0),
            'surprice_vat_percentage' => (float) ($totalCharge['VATPercentage'] ?? 0),
        ];
    }

    // ─── RESERVATION ────────────────────────────────────────────────────

    public function createReservation(array $payload): ?array
    {
        return $this->makeRequest('POST', 'v1/reservation', $payload);
    }

    public function getReservation(string $orderId): ?array
    {
        return $this->makeRequest('GET', "v1/reservation/{$orderId}");
    }

    public function cancelReservation(string $orderId, ?string $reason = null): ?array
    {
        $data = [];
        if ($reason !== null) {
            $data['cancellation_reason'] = $reason;
        }
        return $this->makeRequest('PUT', "v1/reservation/{$orderId}/cancel", $data);
    }

    public function amendReservation(string $orderId, array $payload): ?array
    {
        return $this->makeRequest('PUT', "v1/reservation/{$orderId}/amend", $payload);
    }

    public function commitReservation(string $orderId): ?array
    {
        return $this->makeRequest('PUT', "v1/reservation/{$orderId}/commit");
    }

    // ─── HELPERS ────────────────────────────────────────────────────────

    private function splitDescription(string $description): array
    {
        $cleaned = preg_replace('/\s+or\s+similar$/i', '', $description);
        $parts = preg_split('/\s+/', trim($cleaned), 2);

        $brand = $parts[0] ?? 'Surprice';
        $model = $parts[1] ?? $cleaned;

        return [$brand, $model];
    }

    private function normalizeTransmission(string $transmission): string
    {
        $lower = strtolower($transmission);
        if (str_contains($lower, 'auto')) {
            return 'Automatic';
        }
        return 'Manual';
    }

    private function normalizeFuel(?string $sippCode): string
    {
        if ($sippCode === null || strlen($sippCode) < 4) {
            return 'Petrol';
        }
        $fuelChar = strtoupper($sippCode[3]);
        return match ($fuelChar) {
            'D' => 'Diesel',
            'E', 'C' => 'Electric',
            'H' => 'Hybrid',
            'Q' => 'Hybrid',
            'R' => 'Petrol',
            'N' => 'Petrol',
            default => 'Petrol',
        };
    }

    private function mapExtras(array $rawExtras): array
    {
        $extras = [];
        foreach ($rawExtras as $extra) {
            $calcInfo = $extra['calculationInfo'] ?? [];
            $extras[] = [
                'id' => $extra['description'] ?? '',
                'name' => $extra['detailedDescription'] ?? $extra['description'] ?? '',
                'code' => $extra['description'] ?? '',
                'price' => (float) ($extra['amount'] ?? 0),
                'price_per_day' => (float) ($calcInfo['unitCharge'] ?? 0),
                'currency' => $extra['currencyCode'] ?? 'EUR',
                'per_day' => ($calcInfo['unitName'] ?? '') === 'Day',
                'allow_quantity' => (bool) ($extra['allowQuantity'] ?? false),
                'purpose' => (int) ($extra['purpose'] ?? 0),
                'tax_inclusive' => (bool) ($extra['taxInclusive'] ?? true),
            ];
        }
        return $extras;
    }

    private function mapSurcharges(array $charges): array
    {
        $surcharges = [];
        foreach ($charges as $charge) {
            if (($charge['includedInRate'] ?? false) === true) {
                continue; // Skip product cost itself
            }
            $calcInfo = $charge['calculationInfo'] ?? [];
            $surcharges[] = [
                'code' => $charge['description'] ?? '',
                'name' => $charge['detailedDescription'] ?? $charge['description'] ?? '',
                'amount' => (float) ($charge['amount'] ?? 0),
                'per_day' => (float) ($calcInfo['unitCharge'] ?? 0),
                'currency' => $charge['currencyCode'] ?? 'EUR',
                'included_in_total' => (bool) ($charge['includedInEstTotalInd'] ?? false),
                'purpose' => (int) ($charge['purpose'] ?? 0),
            ];
        }
        return $surcharges;
    }

    /**
     * Format Surprice operationTime into opening_hours array compatible with BookingExtrasStep.
     * Expected format: [{ name: 'Monday - Sunday', open: '07:00', close: '22:00' }, ...]
     */
    private function formatOperationTime(array $operationTimes): array
    {
        $hours = [];
        foreach ($operationTimes as $block) {
            $days = $block['days'] ?? [];
            $schedule = $block['schedule'] ?? [];
            $normal = $schedule['normal'] ?? [];

            if (empty($days) || empty($normal)) {
                continue;
            }

            // Summarize days: if all 7, show "Monday - Sunday"
            $dayLabel = count($days) === 7
                ? ($days[0] . ' - ' . $days[6])
                : implode(', ', $days);

            $hours[] = [
                'name' => $dayLabel,
                'open' => substr($normal['start'] ?? '00:00:00', 0, 5),
                'close' => substr($normal['end'] ?? '23:59:00', 0, 5),
            ];
        }
        return $hours;
    }

    private function buildFeatures(array $vehicle): array
    {
        $features = [];
        if ($vehicle['airConditionInd'] ?? false) {
            $features[] = 'Air Conditioning';
        }
        if (($vehicle['transmissionType'] ?? '') === 'Automatic') {
            $features[] = 'Automatic Transmission';
        }
        return $features;
    }
}
