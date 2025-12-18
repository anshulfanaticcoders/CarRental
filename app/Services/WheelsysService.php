<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WheelsysService
{
    private $baseUrl;
    private $accountNo;
    private $linkCode;
    private $agentCode;
    private $maxRetries = 3;
    private $circuitBreakerThreshold = 5;
    private $circuitBreakerTimeout = 60;
    private $failures = 0;
    private $lastFailureTime = 0;
    private $circuitOpen = false;

    public function __construct()
    {
        $this->baseUrl = config('services.wheelsys.base_url');
        $this->accountNo = config('services.wheelsys.account_no');
        $this->linkCode = config('services.wheelsys.link_code');
        $this->agentCode = config('services.wheelsys.agent_code');

        if (empty($this->baseUrl) || empty($this->linkCode) || empty($this->agentCode) || empty($this->accountNo)) {
            throw new \Exception("Wheelsys credentials are not configured correctly.");
        }
    }

    /**
     * Fetches available vehicles based on search criteria.
     * Uses the price-quote endpoint with retry mechanism and circuit breaker
     */
    public function getVehicles($pickupStation, $returnStation, $dateFrom, $timeFrom, $dateTo, $timeTo, $options = [])
    {
        // Check circuit breaker
        if ($this->isCircuitOpen()) {
            throw new \Exception('Wheelsys API temporarily unavailable due to repeated failures');
        }

        $url = $this->baseUrl . "price-quote_{$this->linkCode}.html";
        $params = [
            'agent' => $this->agentCode,
            'DATE_FROM' => $dateFrom,
            'TIME_FROM' => $timeFrom,
            'DATE_TO' => $dateTo,
            'TIME_TO' => $timeTo,
            'PICKUP_STATION' => $pickupStation,
            'RETURN_STATION' => $returnStation,
            'format' => 'json'
        ];

        return $this->executeWithRetry(function () use ($url, $params) {
            Log::info('Wheelsys API Request (getVehicles): ' . $url, ['params' => $params]);

            $response = Http::timeout(30)->get($url, $params);

            if ($response->successful()) {
                Log::info('Wheelsys API Response received successfully');
                $responseData = $response->json();

                // Debug: Log the actual response structure
                Log::debug('Wheelsys API response structure', [
                    'is_array' => is_array($responseData),
                    'keys' => is_array($responseData) ? array_keys($responseData) : 'not_array',
                    'response_sample' => is_array($responseData) ? json_encode(array_slice($responseData, 0, 2, true)) : 'not_array'
                ]);

                // Validate response structure
                if (!$this->validateApiResponse($responseData)) {
                    throw new \Exception('Invalid API response structure');
                }

                // Reset failures on success
                $this->resetCircuitBreaker();

                return $responseData;
            }

            throw new \Exception('Wheelsys API Error: ' . $response->status() . ' - ' . $response->body());
        }, 'getVehicles');
    }

    /**
     * Fetches all available rental stations.
     * Uses the stations endpoint with retry mechanism
     */
    public function getStations()
    {
        if ($this->isCircuitOpen()) {
            throw new \Exception('Wheelsys API temporarily unavailable due to repeated failures');
        }

        $url = $this->baseUrl . "stations_{$this->linkCode}.html";
        $params = [
            'agent' => $this->agentCode,
            'format' => 'json'
        ];

        return $this->executeWithRetry(function () use ($url, $params) {
            Log::info('Wheelsys API Request (getStations): ' . $url);

            $response = Http::timeout(30)->get($url, $params);

            if ($response->successful()) {
                Log::info('Wheelsys API Response (getStations) received successfully');
                $responseData = $response->json();

                if (!$this->validateApiResponse($responseData)) {
                    throw new \Exception('Invalid API response structure');
                }

                $this->resetCircuitBreaker();
                return $responseData;
            }

            throw new \Exception('Wheelsys API Error (getStations): ' . $response->status() . ' - ' . $response->body());
        }, 'getStations');
    }

    /**
     * Fetches vehicle groups/categories.
     * Uses the groups endpoint with retry mechanism
     */
    public function getVehicleGroups()
    {
        if ($this->isCircuitOpen()) {
            throw new \Exception('Wheelsys API temporarily unavailable due to repeated failures');
        }

        $url = $this->baseUrl . "groups_{$this->linkCode}.html";
        $params = [
            'agent' => $this->agentCode,
            'format' => 'json'
        ];

        return $this->executeWithRetry(function () use ($url, $params) {
            Log::info('Wheelsys API Request (getVehicleGroups): ' . $url);

            $response = Http::timeout(30)->get($url, $params);

            if ($response->successful()) {
                Log::info('Wheelsys API Response (getVehicleGroups) received successfully');
                $responseData = $response->json();

                if (!$this->validateApiResponse($responseData)) {
                    throw new \Exception('Invalid API response structure');
                }

                $this->resetCircuitBreaker();
                return $responseData;
            }

            throw new \Exception('Wheelsys API Error (getVehicleGroups): ' . $response->status() . ' - ' . $response->body());
        }, 'getVehicleGroups');
    }

    /**
     * Fetches available options/extras.
     * Uses the options endpoint with retry mechanism
     */
    public function getOptions()
    {
        if ($this->isCircuitOpen()) {
            throw new \Exception('Wheelsys API temporarily unavailable due to repeated failures');
        }

        $url = $this->baseUrl . "options_{$this->linkCode}.html";
        $params = [
            'agent' => $this->agentCode,
            'format' => 'json'
        ];

        return $this->executeWithRetry(function () use ($url, $params) {
            Log::info('Wheelsys API Request (getOptions): ' . $url);

            $response = Http::timeout(30)->get($url, $params);

            if ($response->successful()) {
                Log::info('Wheelsys API Response (getOptions) received successfully');
                $responseData = $response->json();

                if (!$this->validateApiResponse($responseData)) {
                    throw new \Exception('Invalid API response structure');
                }

                $this->resetCircuitBreaker();
                return $responseData;
            }

            throw new \Exception('Wheelsys API Error (getOptions): ' . $response->status() . ' - ' . $response->body());
        }, 'getOptions');
    }

    /**
     * Converts Wheelsys vehicle rate to standard vehicle object format
     */
    public function convertToStandardVehicle($rate, $pickupStation, $locationLat = null, $locationLng = null, $locationAddress = null)
    {
        // Check for required fields using correct API response structure
        if (!isset($rate['GroupCode'])) {
            Log::error('Vehicle rate missing required fields', ['rate' => $rate]);
            return null;
        }

        // Extract brand from sample model
        $brand = $this->extractBrandFromModel($rate['SampleModel'] ?? '');

        // Convert price from integer to actual value (divide by 100)
        $pricePerDay = isset($rate['TotalRate']) ? (float) ($rate['TotalRate'] / 100) : 0.0;

        // Debug logging
        Log::info('Wheelsys Vehicle Processing', [
            'group_code' => $rate['GroupCode'] ?? 'N/A',
            'total_rate' => $rate['TotalRate'] ?? 'N/A',
            'price_per_day' => $pricePerDay,
            'availability' => $rate['Availability'] ?? 'N/A'
        ]);

        // Robust SIPP/ACRISS Extraction
        // Try 'Acriss', then 'GroupCode', then 'Category'
        $candidates = [
            $rate['Acriss'] ?? '',
            $rate['GroupCode'] ?? '',
            $rate['Category'] ?? '',
            $rate['SampleModel'] ?? '' // Last resort search
        ];

        $acrissCode = '';
        foreach ($candidates as $candidate) {
            if (empty($candidate))
                continue;
            // Look for 4 uppercase letters that look like a valid SIPP/ACRISS code
            // Pattern: Start of word, 4 uppercase letters, End of word
            if (preg_match_all('/\b[A-Z]{4}\b/', $candidate, $matches)) {
                foreach ($matches[0] as $match) {
                    // Basic validation against 1st char (Category) known letters
                    if (strpos('MNEHCDIJSRFGPULWOX', $match[0]) !== false) {
                        $acrissCode = $match;
                        break 2; // Found valid code
                    }
                }
            }
        }

        $parsedAcriss = $this->parseAcrissCode($acrissCode);

        return [
            'id' => 'wheelsys_' . ($rate['GroupCode'] ?? 'unknown') . '_' . uniqid(),
            'source' => 'wheelsys',
            'brand' => $brand,
            'model' => $rate['SampleModel'] ?? 'Wheelsys Vehicle',
            'category' => !empty($parsedAcriss['category']) && $parsedAcriss['category'] !== 'Unknown'
                ? $parsedAcriss['category']
                : ($rate['Category'] ?? ''),
            'group_code' => $rate['GroupCode'] ?? '',
            'acriss_code' => $acrissCode,
            'image' => $this->getVehicleImage($rate['ImageUrl'] ?? '', $rate['Acriss'] ?? '', $rate['GroupCode'] ?? ''),
            'price_per_day' => $pricePerDay,
            'price_per_week' => $pricePerDay * 7,
            'price_per_month' => $pricePerDay * 30,
            'currency' => 'USD',

            'doors' => (int) ($rate['Doors'] ?? 4),
            'bags' => (int) ($rate['Bags'] ?? 0),
            'suitcases' => (int) ($rate['Suitcases'] ?? 0),
            'transmission' => $parsedAcriss['transmission'],
            'fuel' => $parsedAcriss['fuel'],
            'seating_capacity' => (int) ($rate['Pax'] ?? $parsedAcriss['seating_capacity']),
            'mileage' => null, // Removed hardcoded 'unlimited' as it was appearing in Fuel Consumption slot
            'latitude' => (float) $locationLat,
            'longitude' => (float) $locationLng,
            'full_vehicle_address' => $locationAddress,
            'provider_pickup_id' => $pickupStation,
            'availability' => $rate['Availability'] === 'AVAILABLE',
            'benefits' => (object) [
                'cancellation_available_per_day' => false, // Default false as API key is unknown
                'limited_km_per_day' => isset($rate['Unlimited']) ? !$rate['Unlimited'] : true, // limited if NOT unlimited
                'minimum_driver_age' => null, // Removed hardcoded 21
                'fuel_policy' => isset($rate['FuelPolicy']) ? ucwords(str_replace('_', ' ', strtolower($rate['FuelPolicy']))) : 'Full to Full',
                'unlimited_mileage' => $rate['Unlimited'] ?? false,
                // If Unlimited is true, ignore IncKlm (even if it's 99999). Only show IncKlm if it's NOT unlimited.
                'included_km' => ($rate['Unlimited'] ?? false) ? null : (isset($rate['IncKlm']) ? (int) $rate['IncKlm'] : null),
                'currency' => 'USD',
                'tax_inclusive' => $rate['TaxInclusive'] ?? true,
            ],
            'review_count' => 0,
            'average_rating' => 0,
            'products' => [],
            'features' => !empty($parsedAcriss['air_conditioning']) ? ['Air Conditioning'] : [],
            'extras' => $this->processOptions($rate['Options'] ?? []),
            'insurance_options' => [],
            'wheelsys_data' => $rate, // Store raw data for debugging
        ];
    }

    /**
     * Extract brand name from vehicle model
     */
    private function extractBrandFromModel($model)
    {
        if (empty($model)) {
            return 'Wheelsys';
        }

        // Common car brands to extract from model names
        $brands = ['Nissan', 'Toyota', 'Buick', 'Kia', 'Chevy', 'Ford', 'Honda', 'Hyundai', 'Mazda', 'Volkswagen'];

        foreach ($brands as $brand) {
            if (stripos($model, $brand) !== false) {
                return $brand;
            }
        }

        // If no brand found, use first word as brand
        $words = explode(' ', $model);
        return $words[0] ?? 'Wheelsys';
    }

    /**
     * Get vehicle image URL
     */
    private function getVehicleImage($imageUrl, $sippCode = '', $groupCode = '')
    {
        // If we have a valid ImageUrl from the API, use it
        if (!empty($imageUrl) && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            return $imageUrl;
        }

        // Fallback to placeholder image
        return url('/images/wheelsys-placeholder.jpg');
    }

    /**
     * Process options/extras data
     */
    private function processOptions($options)
    {
        if (empty($options) || !is_array($options)) {
            return [];
        }

        $processedOptions = [];
        foreach ($options as $option) {
            $processedOptions[] = [
                'code' => $option['Code'] ?? '',
                'name' => $this->getOptionName($option['Code'] ?? ''),
                'rate' => isset($option['Rate']) ? (float) ($option['Rate'] / 100) : 0.0,
                'mandatory' => $option['Mandatory'] ?? false,
                'inclusive' => $option['Inclusive'] ?? false,
                'charge_type' => $option['ChargeType'] ?? '',
                'description' => $this->getOptionDescription($option['Code'] ?? ''),
            ];
        }

        return $processedOptions;
    }

    /**
     * Get option name from code
     */
    private function getOptionName($code)
    {
        $optionNames = [
            'EI' => 'Excess Insurance',
            'CDW' => 'Collision Damage Waiver',
            'PCDW' => 'Premium Collision Damage Waiver',
            'BBS' => 'Baby Booster Seat',
            'SLI' => 'Supplemental Liability Insurance',
            'GPS' => 'GPS Navigation',
            'ADD' => 'Additional Driver',
            'TOL' => 'Toll Pass',
            'YD' => 'Young Driver Surcharge',
            'APD' => 'Airport Parking',
            'OS' => 'Off-Site Airport',
            'UM' => 'Underage Driver',
            'RS' => 'Roadside Service',
            'PPI' => 'Personal Protection Insurance',
            'LF' => 'Loss Damage Waiver',
            'UPG' => 'Vehicle Upgrade',
            'SD' => 'Sports Equipment',
            'TRV' => 'Travel Insurance',
            'TRVA' => 'Travel Insurance Plus',
            'WT' => 'Winter Tires',
            'DR' => 'Driver Report',
            'PGAS' => 'Pre-Paid Gas',
            'SC' => 'Service Charge',
        ];

        return $optionNames[$code] ?? $code;
    }

    /**
     * Get option description from code
     */
    private function getOptionDescription($code)
    {
        $descriptions = [
            'EI' => 'Reduces your excess liability to zero',
            'CDW' => 'Reduces your collision damage excess',
            'PCDW' => 'Premium collision damage waiver with lower excess',
            'BBS' => 'Baby booster seat for children',
            'SLI' => 'Supplemental liability insurance coverage',
            'GPS' => 'GPS navigation system',
            'ADD' => 'Additional authorized driver',
            'TOL' => 'Electronic toll pass for convenient travel',
            'YD' => 'Young driver surcharge for drivers under 25',
            'APD' => 'Airport parking services',
            'OS' => 'Off-site airport location',
            'UM' => 'Underage driver surcharge',
            'RS' => 'Roadside assistance service',
            'PPI' => 'Personal protection insurance',
            'LF' => 'Loss damage waiver coverage',
            'UPG' => 'Vehicle upgrade when available',
            'SD' => 'Sports equipment rental',
            'TRV' => 'Travel insurance coverage',
            'TRVA' => 'Premium travel insurance',
            'WT' => 'Winter tires for snow conditions',
            'DR' => 'Driver report service',
            'PGAS' => 'Pre-paid gas option',
            'SC' => 'Service charge and fees',
        ];

        return $descriptions[$code] ?? 'Additional service or coverage option';
    }

    /**
     * Creates a new reservation with the Wheelsys API.
     */
    public function createReservation(array $bookingData)
    {
        if ($this->isCircuitOpen()) {
            throw new \Exception('Wheelsys API temporarily unavailable due to repeated failures');
        }

        $url = $this->baseUrl . "new-res_{$this->linkCode}.html";

        // Prepare parameters for the API call
        $params = [
            'agent' => $this->agentCode,
            'DATE_FROM' => $bookingData['pickup_date'],
            'TIME_FROM' => $bookingData['pickup_time'],
            'DATE_TO' => $bookingData['return_date'],
            'TIME_TO' => $bookingData['return_time'],
            'PICKUP_STATION' => $bookingData['pickup_station_code'],
            'RETURN_STATION' => $bookingData['return_station_code'],
            'group' => $bookingData['vehicle_group_code'],
            'quoteref' => $bookingData['wheelsys_quote_id'] ?? null,
            'first_name' => $bookingData['customer_first_name'],
            'last_name' => $bookingData['customer_last_name'],
            'email' => $bookingData['customer_email'],
            'phone' => $bookingData['customer_phone'],
            'format' => 'json'
        ];

        return $this->executeWithRetry(function () use ($url, $params) {
            Log::info('Wheelsys API Request (createReservation): ' . $url, ['params' => $params]);

            $response = Http::timeout(30)->post($url, $params);

            if ($response->successful()) {
                Log::info('Wheelsys API Response (createReservation) received successfully');
                $responseData = $response->json();

                // You might want to add validation for the reservation response here
                if (!isset($responseData['irn'])) {
                    throw new \Exception('Invalid reservation response structure');
                }

                $this->resetCircuitBreaker();
                return $responseData;
            }

            throw new \Exception('Wheelsys API Error (createReservation): ' . $response->status() . ' - ' . $response->body());
        }, 'createReservation');
    }

    /**
     * Execute API call with retry mechanism and circuit breaker
     */
    private function executeWithRetry(callable $callback, string $operation)
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                return $callback();
            } catch (\Exception $e) {
                $lastException = $e;
                $this->recordFailure();

                Log::warning("Wheelsys API attempt {$attempt} failed for {$operation}: " . $e->getMessage());

                if ($attempt < $this->maxRetries) {
                    $delay = min(pow(2, $attempt), 10); // Exponential backoff, max 10 seconds
                    sleep($delay);
                    Log::info("Retrying Wheelsys API operation {$operation} in {$delay} seconds (attempt {$attempt}/{$this->maxRetries})");
                }
            }
        }

        Log::error("Wheelsys API operation {$operation} failed after {$this->maxRetries} attempts");
        throw $lastException;
    }

    /**
     * Check if circuit breaker is open
     */
    private function isCircuitOpen(): bool
    {
        if ($this->circuitOpen) {
            // Check if timeout has passed
            if (time() - $this->lastFailureTime > $this->circuitBreakerTimeout) {
                $this->circuitOpen = false;
                $this->failures = 0;
                Log::info('Wheelsys circuit breaker reset after timeout');
                return false;
            }
            return true;
        }

        return false;
    }

    /**
     * Record a failure for circuit breaker
     */
    private function recordFailure(): void
    {
        $this->failures++;
        $this->lastFailureTime = time();

        if ($this->failures >= $this->circuitBreakerThreshold) {
            $this->circuitOpen = true;
            Log::warning("Wheelsys circuit breaker opened after {$this->failures} failures");
        }
    }

    /**
     * Reset circuit breaker on success
     */
    private function resetCircuitBreaker(): void
    {
        if ($this->failures > 0) {
            Log::info("Wheelsys circuit breaker reset after {$this->failures} failures");
            $this->failures = 0;
            $this->circuitOpen = false;
        }
    }

    /**
     * Validate API response structure
     */
    private function validateApiResponse($responseData): bool
    {
        if (!is_array($responseData)) {
            Log::error('Wheelsys API response is not an array');
            return false;
        }

        // Based on the actual API response structure you showed me
        // The response might be directly the vehicles object, not wrapped in 'vehicles' key
        if (!isset($responseData['Rates']) && !isset($responseData['vehicles']) && !isset($responseData['Stations'])) {
            Log::warning('Wheelsys API response structure unexpected - checking available keys');
            Log::debug('Available response keys: ' . implode(', ', array_keys($responseData)));
            return true; // Allow processing - let the controller handle it
        }


        return true; // Response seems valid for processing
    }

    /**
     * Get current circuit breaker status for monitoring
     */
    public function getCircuitBreakerStatus(): array
    {
        return [
            'open' => $this->circuitOpen,
            'failures' => $this->failures,
            'threshold' => $this->circuitBreakerThreshold,
            'last_failure_time' => $this->lastFailureTime,
            'timeout' => $this->circuitBreakerTimeout
        ];
    }
    /**
     * Parse ACRISS/SIPP code to get vehicle details
     */
    private function parseAcrissCode($code)
    {
        $code = strtoupper($code ?? '');
        $data = [
            'category' => 'Unknown',
            'transmission' => 'manual', // Default
            'fuel' => 'petrol', // Default
            'seating_capacity' => 4, // Default
            'air_conditioning' => false // Default
        ];

        if (strlen($code) < 4) {
            return $data;
        }

        // Load SIPP codes for Category mapping
        $sippJsonPath = base_path('database/sipp_codes.json');
        if (file_exists($sippJsonPath)) {
            $sippCodes = json_decode(file_get_contents($sippJsonPath), true);

            // 1. Category (1st char)
            $char1 = $code[0];
            if (isset($sippCodes['category'][$char1])) {
                $catData = $sippCodes['category'][$char1];
                $data['category'] = $catData['name'] ?? 'Unknown';
            }
        }

        // 3rd letter: Transmission
        $transmissionChar = $code[2];
        if (in_array($transmissionChar, ['A', 'B', 'D'])) {
            $data['transmission'] = 'automatic';
        }

        // 4th letter: Fuel/AC
        $fuelChar = $code[3];
        if (in_array($fuelChar, ['D', 'Q'])) {
            $data['fuel'] = 'diesel';
        } elseif (in_array($fuelChar, ['H', 'I'])) {
            $data['fuel'] = 'hybrid';
        } elseif (in_array($fuelChar, ['E', 'C'])) {
            $data['fuel'] = 'electric';
        }

        // Check for Air Conditioning (R, D, H, E, L, A, M, V, U denote AC)
        if (in_array($fuelChar, ['R', 'D', 'H', 'E', 'L', 'A', 'M', 'V', 'U'])) {
            $data['air_conditioning'] = true;
        }

        return $data;
    }
}

