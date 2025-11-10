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
    private $cacheFile;
    private $cacheTime = 3600; // 1 hour cache

    public function __construct()
    {
        $this->baseUrl = config('services.wheelsys.base_url');
        $this->accountNo = config('services.wheelsys.account_no');
        $this->linkCode = config('services.wheelsys.link_code');
        $this->agentCode = config('services.wheelsys.agent_code');
        $this->cacheFile = storage_path('app/cache/wheelsys_cache.json');

        if (empty($this->baseUrl) || empty($this->linkCode) || empty($this->agentCode) || empty($this->accountNo)) {
            throw new \Exception("Wheelsys credentials are not configured correctly.");
        }
    }

    /**
     * Fetches available vehicles based on search criteria.
     * Uses the price-quote endpoint
     */
    public function getVehicles($pickupStation, $returnStation, $dateFrom, $timeFrom, $dateTo, $timeTo, $options = [])
    {
        // Create cache key
        $cacheKey = "vehicles_{$pickupStation}_{$returnStation}_{$dateFrom}_{$timeFrom}_{$dateTo}_{$timeTo}";

        // Check cache first
        $cachedData = $this->getCachedData($cacheKey);
        if ($cachedData) {
            Log::info('Wheelsys: Using cached data for ' . $cacheKey);
            return $cachedData;
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

        try {
            Log::info('Wheelsys API Request (getVehicles): ' . $url, ['params' => $params]);

            $response = Http::timeout(30)->get($url, $params);

            if ($response->successful()) {
                Log::info('Wheelsys API Response received successfully');
                $responseData = $response->json();

                // Cache the response
                $this->setCachedData($cacheKey, $responseData);

                return $responseData;
            }

            Log::error('Wheelsys API Error: ' . $response->status() . ' - ' . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error('Wheelsys API Exception (getVehicles): ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Fetches all available rental stations.
     * Uses the stations endpoint
     */
    public function getStations()
    {
        // Check cache first
        $cachedData = $this->getCachedData('stations');
        if ($cachedData) {
            Log::info('Wheelsys: Using cached stations data');
            return $cachedData;
        }

        $url = $this->baseUrl . "stations_{$this->linkCode}.html";

        $params = [
            'agent' => $this->agentCode,
            'format' => 'json'
        ];

        try {
            Log::info('Wheelsys API Request (getStations): ' . $url);

            $response = Http::timeout(30)->get($url, $params);

            if ($response->successful()) {
                Log::info('Wheelsys API Response (getStations) received successfully');
                $responseData = $response->json();

                // Cache the response for longer time (stations don't change often)
                $this->setCachedData('stations', $responseData, 86400); // 24 hours cache

                return $responseData;
            }

            Log::error('Wheelsys API Error (getStations): ' . $response->status() . ' - ' . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error('Wheelsys API Exception (getStations): ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Fetches vehicle groups/categories.
     * Uses the groups endpoint
     */
    public function getVehicleGroups()
    {
        // Check cache first
        $cachedData = $this->getCachedData('groups');
        if ($cachedData) {
            Log::info('Wheelsys: Using cached groups data');
            return $cachedData;
        }

        $url = $this->baseUrl . "groups_{$this->linkCode}.html";

        $params = [
            'agent' => $this->agentCode,
            'format' => 'json'
        ];

        try {
            Log::info('Wheelsys API Request (getVehicleGroups): ' . $url);

            $response = Http::timeout(30)->get($url, $params);

            if ($response->successful()) {
                Log::info('Wheelsys API Response (getVehicleGroups) received successfully');
                $responseData = $response->json();

                // Cache the response for longer time (groups don't change often)
                $this->setCachedData('groups', $responseData, 86400); // 24 hours cache

                return $responseData;
            }

            Log::error('Wheelsys API Error (getVehicleGroups): ' . $response->status() . ' - ' . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error('Wheelsys API Exception (getVehicleGroups): ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Fetches available options/extras.
     * Uses the options endpoint
     */
    public function getOptions()
    {
        // Check cache first
        $cachedData = $this->getCachedData('options');
        if ($cachedData) {
            Log::info('Wheelsys: Using cached options data');
            return $cachedData;
        }

        $url = $this->baseUrl . "options_{$this->linkCode}.html";

        $params = [
            'agent' => $this->agentCode,
            'format' => 'json'
        ];

        try {
            Log::info('Wheelsys API Request (getOptions): ' . $url);

            $response = Http::timeout(30)->get($url, $params);

            if ($response->successful()) {
                Log::info('Wheelsys API Response (getOptions) received successfully');
                $responseData = $response->json();

                // Cache the response for longer time (options don't change often)
                $this->setCachedData('options', $responseData, 86400); // 24 hours cache

                return $responseData;
            }

            Log::error('Wheelsys API Error (getOptions): ' . $response->status() . ' - ' . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error('Wheelsys API Exception (getOptions): ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Converts Wheelsys vehicle rate to standard vehicle object format
     */
    public function convertToStandardVehicle($rate, $pickupStation, $locationLat = null, $locationLng = null, $locationAddress = null)
    {
        if (!isset($rate['GroupCode']) || $rate['Availability'] !== 'AVAILABLE') {
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

        return [
            'id' => 'wheelsys_' . $rate['GroupCode'] . '_' . uniqid(),
            'source' => 'wheelsys',
            'brand' => $brand,
            'model' => $rate['SampleModel'] ?? 'Wheelsys Vehicle',
            'category' => $rate['Category'] ?? '',
            'group_code' => $rate['GroupCode'] ?? '',
            'acriss_code' => $rate['Acriss'] ?? '',
            'image' => $this->getVehicleImage($rate['ImageUrl'] ?? '', $rate['Acriss'] ?? '', $rate['GroupCode'] ?? ''),
            'price_per_day' => $pricePerDay,
            'price_per_week' => $pricePerDay * 7,
            'price_per_month' => $pricePerDay * 30,
            'currency' => 'USD',
            'seating_capacity' => (int) ($rate['Pax'] ?? 4),
            'doors' => (int) ($rate['Doors'] ?? 4),
            'bags' => (int) ($rate['Bags'] ?? 0),
            'suitcases' => (int) ($rate['Suitcases'] ?? 0),
            'transmission' => 'automatic', // Default as not provided by Wheelsys
            'fuel' => 'petrol', // Default as not provided by Wheelsys
            'mileage' => 'unlimited',
            'latitude' => (float) $locationLat,
            'longitude' => (float) $locationLng,
            'full_vehicle_address' => $locationAddress,
            'provider_pickup_id' => $pickupStation,
            'availability' => $rate['Availability'] === 'AVAILABLE',
            'benefits' => (object) [
                'cancellation_available_per_day' => true,
                'limited_km_per_day' => false,
                'minimum_driver_age' => 21,
                'fuel_policy' => $rate['FuelPolicy'] ?? 'full_to_full',
                'unlimited_mileage' => $rate['Unlimited'] ?? true,
                'included_km' => isset($rate['IncKlm']) ? (int) $rate['IncKlm'] : 99999,
                'currency' => 'USD',
                'tax_inclusive' => $rate['TaxInclusive'] ?? true,
            ],
            'review_count' => 0,
            'average_rating' => 0,
            'products' => [],
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
        // Try to construct image URL using SIPP code or group code if ImageUrl is empty
        if (empty($imageUrl)) {
            $imgRoot = "https://wheels-assets.s3.eu-central-1.amazonaws.com/";

            // Use SIPP code first, then group code as fallback
            $imageIdentifier = !empty($sippCode) ? $sippCode : $groupCode;

            if (!empty($imageIdentifier)) {
                return "{$imgRoot}{$imageIdentifier}.png";
            }

            return 'https://via.placeholder.com/300x200/4CAF50/FFFFFF?text=' . urlencode($imageIdentifier ?? 'Car');
        }

        // If it's already a full URL, return as is
        if (strpos($imageUrl, 'http') === 0) {
            return $imageUrl;
        }

        // Construct full URL if it's a relative path
        return "https://wheels-assets.s3.eu-central-1.amazonaws.com/{$imageUrl}";
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
     * Cache management methods
     */
    private function getCachedData($key)
    {
        if (!file_exists($this->cacheFile)) {
            return null;
        }

        $cache = json_decode(file_get_contents($this->cacheFile), true);
        if (!$cache || !isset($cache[$key])) {
            return null;
        }

        $item = $cache[$key];
        if (time() > $item['expires']) {
            unset($cache[$key]);
            file_put_contents($this->cacheFile, json_encode($cache));
            return null;
        }

        return $item['data'];
    }

    private function setCachedData($key, $data, $customCacheTime = null)
    {
        $cacheTime = $customCacheTime ?? $this->cacheTime;
        $cache = [];

        if (file_exists($this->cacheFile)) {
            $cache = json_decode(file_get_contents($this->cacheFile), true) ?: [];
        }

        $cache[$key] = [
            'data' => $data,
            'expires' => time() + $cacheTime
        ];

        // Ensure cache directory exists
        $cacheDir = dirname($this->cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        file_put_contents($this->cacheFile, json_encode($cache));
    }

    /**
     * Clear cache
     */
    public function clearCache()
    {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
        Log::info('Wheelsys cache cleared');
    }
}