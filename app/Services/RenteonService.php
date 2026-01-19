<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RenteonService
{
    private $baseUrl;
    private $username;
    private $password;
    private $providerCode;

    public function __construct()
    {
        $config = config("services.renteon");

        if (empty($config['username']) || empty($config['password']) || empty($config['base_url'])) {
            throw new \Exception("Credentials or URL for Renteon provider are not configured correctly.");
        }

        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->baseUrl = rtrim($config['base_url'], '/');
        $this->providerCode = $config['provider_code'] ?? 'demo';
    }

    /**
     * Make authenticated HTTP request to Renteon API
     */
    private function makeRequest($method, $endpoint, $data = [])
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');

        try {
            Log::info("Renteon API Request: {$method} {$url}", ['data' => $data]);

            $response = Http::withBasicAuth($this->username, $this->password)
                ->withOptions([
                    'connect_timeout' => 120,
                    'timeout' => 120,
                ])
                ->timeout(120)
                ->acceptJson()
                ->{$method}($url, $data);

            if ($response->successful()) {
                Log::info("Renteon API Response received successfully", [
                    'status' => $response->status(),
                    'url' => $url
                ]);
                return $response->json();
            }

            Log::error("Renteon API Error: " . $response->status() . " - " . $response->body(), [
                'url' => $url,
                'data' => $data
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Renteon API Exception: " . $e->getMessage(), [
                'url' => $url,
                'data' => $data
            ]);
            return null;
        }
    }

    /**
     * Fetch all available locations from Renteon API
     */
    public function getLocations()
    {
        return $this->makeRequest('GET', 'api/setup/locations');
    }

    /**
     * Get location by code
     */
    public function getLocationByCode($code)
    {
        $locations = $this->getLocations();
        if (!$locations) {
            return null;
        }

        foreach ($locations as $location) {
            if ($location['Code'] === $code) {
                return $location;
            }
        }

        return null;
    }

    /**
     * Search for available vehicles from Renteon API
     * Calls the /api/bookings/search endpoint with POST
     *
     * @param string $pickupCode Pickup location code
     * @param string $dropoffCode Dropoff location code
     * @param string $startDate Pickup date (YYYY-MM-DD)
     * @param string $startTime Pickup time (HH:MM)
     * @param string $endDate Dropoff date (YYYY-MM-DD)
     * @param string $endTime Dropoff time (HH:MM)
     * @param array $options Additional options
     * @param string|null $providerCode Specific provider to search (null for default from config)
     * @return array|null Vehicle search results
     */
    public function getVehicles($pickupCode, $dropoffCode, $startDate, $startTime, $endDate, $endTime, $options = [], $providerCode = null)
    {
        $provider = $providerCode ?? $this->providerCode;

        $data = [
            'Provider' => $provider,
            'PickupLocationCode' => $pickupCode,
            'DropoffLocationCode' => $dropoffCode ?? $pickupCode,
            'PickupDate' => $startDate,
            'DropoffDate' => $endDate
        ];

        Log::info('Renteon vehicle search API call', [
            'provider' => $provider,
            'pickup_code' => $pickupCode,
            'dropoff_code' => $dropoffCode ?? $pickupCode,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        $response = $this->makeRequest('POST', 'api/bookings/search', $data);

        if ($response === null) {
            Log::warning('Renteon API returned null response', [
                'pickup_code' => $pickupCode,
                'provider' => $provider
            ]);
        } elseif (empty($response)) {
            Log::info('Renteon API returned empty vehicle list', [
                'pickup_code' => $pickupCode,
                'provider' => $provider
            ]);
        }

        return $response;
    }

    /**
     * Search for available vehicles from all Renteon providers
     * Aggregates results from all providers that have vehicles for the given location/dates
     *
     * @param string $pickupCode Pickup location code
     * @param string $dropoffCode Dropoff location code
     * @param string $startDate Pickup date (YYYY-MM-DD)
     * @param string $startTime Pickup time (HH:MM)
     * @param string $endDate Dropoff date (YYYY-MM-DD)
     * @param string $endTime Dropoff time (HH:MM)
     * @param array $options Additional options
     * @param array $providerCodes Optional array of specific provider codes to search (empty = all providers)
     * @return array Aggregated vehicle results from all providers
     */
    public function getVehiclesFromAllProviders($pickupCode, $dropoffCode, $startDate, $startTime, $endDate, $endTime, $options = [], $providerCodes = [])
    {
        // Get the list of providers to search
        if (empty($providerCodes)) {
            // Get all available providers from Renteon
            $providers = $this->getProviders();
            if (!$providers) {
                Log::warning('Failed to get providers list from Renteon API');
                return [];
            }
            $providerCodes = array_column($providers, 'Code');
        }

        Log::info('Renteon: Searching vehicles from multiple providers', [
            'provider_count' => count($providerCodes),
            'providers' => $providerCodes,
            'pickup_code' => $pickupCode,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        $allVehicles = [];
        $providersWithVehicles = [];
        $providersWithoutVehicles = [];

        // Search each provider for vehicles
        foreach ($providerCodes as $providerCode) {
            try {
                $vehicles = $this->getVehicles(
                    $pickupCode,
                    $dropoffCode,
                    $startDate,
                    $startTime,
                    $endDate,
                    $endTime,
                    $options,
                    $providerCode
                );

                if ($vehicles && !empty($vehicles)) {
                    // Add provider info to each vehicle
                    foreach ($vehicles as &$vehicle) {
                        $vehicle['provider_code'] = $providerCode;
                        $vehicle['provider_source'] = 'renteon';
                    }
                    $allVehicles = array_merge($allVehicles, $vehicles);
                    $providersWithVehicles[] = $providerCode;
                } else {
                    $providersWithoutVehicles[] = $providerCode;
                }
            } catch (\Exception $e) {
                Log::warning("Error searching vehicles from provider {$providerCode}: " . $e->getMessage());
                $providersWithoutVehicles[] = $providerCode;
            }
        }

        Log::info('Renteon: Multi-provider search complete', [
            'total_vehicles' => count($allVehicles),
            'providers_with_vehicles' => $providersWithVehicles,
            'providers_without_vehicles' => $providersWithoutVehicles
        ]);

        return $allVehicles;
    }

    /**
     * Get vehicle categories (SIPP codes) from Renteon
     */
    public function getCarCategories()
    {
        return $this->makeRequest('GET', 'api/setup/carCategories');
    }

    /**
     * Get services/equipment available from Renteon
     */
    public function getServices()
    {
        return $this->makeRequest('GET', 'api/setup/services');
    }

    /**
     * Get provider information
     */
    public function getProviders()
    {
        return $this->makeRequest('GET', 'api/setup/providers');
    }

    /**
     * Get provider details by code
     */
    public function getProviderDetails($providerCode)
    {
        return $this->makeRequest('GET', 'api/setup/provider/' . $providerCode);
    }

    /**
     * Create booking
     * This will need to be implemented based on the actual booking endpoint
     */
    public function createBooking($vehicleData, $customerData, $bookingData)
    {
        // This is a placeholder - we'll need to check the actual booking endpoint
        // from the Swagger documentation to implement this correctly

        $data = [
            'provider_code' => $this->providerCode,
            'vehicle' => $vehicleData,
            'customer' => $customerData,
            'booking' => $bookingData,
        ];

        // Placeholder endpoint - need to verify actual endpoint from Swagger
        return $this->makeRequest('POST', 'api/bookings', $data);
    }

    /**
     * Get booking status
     */
    public function getBookingStatus($bookingId)
    {
        return $this->makeRequest('GET', "api/bookings/{$bookingId}");
    }

    /**
     * Cancel booking
     */
    public function cancelBooking($bookingId)
    {
        return $this->makeRequest('DELETE', "api/bookings/{$bookingId}");
    }

    /**
     * Transform Renteon location data to unified format
     */
    public function transformLocation($location)
    {
        return [
            'code' => $location['Code'],
            'name' => $location['Name'],
            'country_code' => $location['CountryCode'],
            'type' => $location['Type'],
            'category' => $location['Category'],
            'path' => $location['Path'],
            'provider' => 'renteon',
            'full_address' => $location['Path'],
        ];
    }

    /**
     * Parse location response for dropoff locations (used by GreenMotionController)
     */
    public function parseLocationResponse()
    {
        $locations = $this->getLocations();
        if (!$locations) {
            return [];
        }

        $parsedLocations = [];
        foreach ($locations as $location) {
            // Transform to match the structure expected by the frontend dropoff selector
            // Usually needs id/code and name
            $parsedLocations[] = [
                'location_id' => $location['Code'], // Map Code to location_id
                'name' => $location['Name'],
                'address' => $location['Path'] ?? '',
                'code' => $location['Code'],
                'provider' => 'renteon'
            ];
        }

        return $parsedLocations;
    }

    /**
     * Get available vehicles from Renteon and transform to unified format
     * Uses the default provider from config
     */
    public function getTransformedVehicles($pickupCode, $dropoffCode, $startDate, $startTime, $endDate, $endTime, $options = [], $locationLat = null, $locationLng = null, $locationName = null, $rentalDays = 1)
    {
        $vehicles = $this->getVehicles($pickupCode, $dropoffCode, $startDate, $startTime, $endDate, $endTime, $options);

        if (!$vehicles) {
            return [];
        }

        // Transform each vehicle to unified format
        $transformedVehicles = [];
        foreach ($vehicles as $vehicle) {
            $transformedVehicles[] = $this->transformVehicle($vehicle, $pickupCode, $locationLat, $locationLng, $locationName, $rentalDays);
        }

        return $transformedVehicles;
    }

    /**
     * Get available vehicles from ALL Renteon providers and transform to unified format
     * Aggregates vehicles from all providers that have availability
     *
     * @param string $pickupCode Pickup location code
     * @param string $dropoffCode Dropoff location code
     * @param string $startDate Pickup date (YYYY-MM-DD)
     * @param string $startTime Pickup time (HH:MM)
     * @param string $endDate Dropoff date (YYYY-MM-DD)
     * @param string $endTime Dropoff time (HH:MM)
     * @param array $options Additional options
     * @param array $providerCodes Optional array of specific provider codes to search (empty = all providers)
     * @param float|null $locationLat Location latitude
     * @param float|null $locationLng Location longitude
     * @param string|null $locationName Location name
     * @param int $rentalDays Number of rental days
     * @return array Transformed vehicles from all providers
     */
    public function getTransformedVehiclesFromAllProviders($pickupCode, $dropoffCode, $startDate, $startTime, $endDate, $endTime, $options = [], $providerCodes = [], $locationLat = null, $locationLng = null, $locationName = null, $rentalDays = 1)
    {
        $vehicles = $this->getVehiclesFromAllProviders(
            $pickupCode,
            $dropoffCode,
            $startDate,
            $startTime,
            $endDate,
            $endTime,
            $options,
            $providerCodes
        );

        if (!$vehicles) {
            return [];
        }

        // Transform each vehicle to unified format
        $transformedVehicles = [];
        foreach ($vehicles as $vehicle) {
            $transformedVehicles[] = $this->transformVehicle($vehicle, $pickupCode, $locationLat, $locationLng, $locationName, $rentalDays);
        }

        return $transformedVehicles;
    }

    /**
     * Transform vehicle data to unified format
     * This will need to be implemented based on actual vehicle data structure
     */
    private function transformVehicle($vehicle, $pickupCode, $locationLat = null, $locationLng = null, $locationName = null, $rentalDays = 1)
    {
        // Parse SIPP code if available to extract vehicle details
        $sippCode = $vehicle['sipp_code'] ?? $vehicle['acriss_code'] ?? null;
        $parsedSipp = $this->parseSippCode($sippCode);

        // Get provider code if available (for multi-provider searches)
        $providerCode = $vehicle['provider_code'] ?? $this->providerCode;

        $brand = $vehicle['make'] ?? 'Renteon';
        $model = $vehicle['model'] ?? 'Renteon Vehicle';
        $category = $parsedSipp['category'] ?? ($vehicle['category'] ?? 'Unknown');

        // Create unique ID that includes provider code to avoid conflicts
        $vehicleId = $vehicle['id'] ?? md5($providerCode . '_' . $pickupCode . '_' . ($sippCode ?? 'unknown'));

        return [
            'id' => 'renteon_' . $providerCode . '_' . $vehicleId,
            'source' => 'renteon',
            'provider_code' => $providerCode,
            'brand' => $brand,
            'model' => $model,
            'category' => $category,
            'sipp_code' => $sippCode,
            'image' => $vehicle['image_url'] ?? $vehicle['image'] ?? '/images/dummyCarImaage.png',
            'price_per_day' => (float) ($vehicle['daily_rate'] ?? 0),
            'total_price' => (float) (($vehicle['daily_rate'] ?? 0) * $rentalDays),
            'price_per_week' => (float) (($vehicle['daily_rate'] ?? 0) * 7),
            'price_per_month' => (float) (($vehicle['daily_rate'] ?? 0) * 30),
            'currency' => $vehicle['currency'] ?? 'EUR',
            'transmission' => $parsedSipp['transmission'] ?? ($vehicle['transmission'] ?? 'Manual'),
            'fuel' => $parsedSipp['fuel'] ?? ($vehicle['fuel_type'] ?? 'Petrol'),
            'seating_capacity' => (int) ($parsedSipp['seating_capacity'] ?? $vehicle['seats'] ?? 4),
            'doors' => (int) ($parsedSipp['doors'] ?? $vehicle['doors'] ?? 4),
            'mileage' => $vehicle['mileage'] ?? 'Unlimited',
            'latitude' => (float) ($locationLat ?? 0),
            'longitude' => (float) ($locationLng ?? 0),
            'full_vehicle_address' => $locationName ?? 'Renteon Location',
            'provider_pickup_id' => $pickupCode,
            'features' => $vehicle['features'] ?? [],
            'airConditioning' => $parsedSipp['air_conditioning'] ?? false,
            'benefits' => [
                'minimum_driver_age' => (int) ($vehicle['min_driver_age'] ?? 21),
                'fuel_policy' => $vehicle['fuel_policy'] ?? 'Full to Full',
            ],
            'products' => [
                [
                    'type' => 'BAS',
                    'total' => (string) (($vehicle['daily_rate'] ?? 0) * $rentalDays),
                    'currency' => $vehicle['currency'] ?? 'EUR',
                ]
            ],
            'options' => [],
            'insurance_options' => [],
        ];
    }

    /**
     * Parse SIPP/ACRISS code to extract vehicle details
     */
    private function parseSippCode($sipp)
    {
        $sipp = strtoupper($sipp ?? '');
        $data = [
            'transmission' => 'manual',
            'fuel' => 'petrol',
            'seating_capacity' => 5,
            'doors' => 4,
            'category' => 'Unknown',
            'air_conditioning' => false,
        ];

        if (strlen($sipp) < 4) {
            return $data;
        }

        // 1. Category (1st char)
        $categoryMap = [
            'M' => 'Mini', 'N' => 'Mini',
            'E' => 'Economy', 'H' => 'Economy',
            'C' => 'Compact', 'D' => 'Compact',
            'I' => 'Intermediate',
            'S' => 'Standard',
            'F' => 'Fullsize',
            'P' => 'Premium', 'L' => 'Luxury',
            'J' => 'SUV', 'G' => 'SUV', 'R' => 'SUV',
            'K' => 'Van', 'V' => 'Van',
        ];

        if (isset($categoryMap[$sipp[0]])) {
            $data['category'] = $categoryMap[$sipp[0]];
        }

        // 2. Transmission (3rd char)
        if (isset($sipp[2])) {
            $data['transmission'] = in_array($sipp[2], ['A', 'B', 'D']) ? 'automatic' : 'manual';
        }

        // 3. Fuel & AC (4th char)
        if (isset($sipp[3])) {
            $fuelMap = [
                'D' => 'diesel', 'Q' => 'diesel',
                'H' => 'hybrid', 'I' => 'hybrid',
                'E' => 'electric', 'C' => 'electric',
                'L' => 'lpg', 'S' => 'hybrid',
            ];
            if (isset($fuelMap[$sipp[3]])) {
                $data['fuel'] = $fuelMap[$sipp[3]];
            }
            // R indicates AC
            $data['air_conditioning'] = ($sipp[3] === 'R');
        }

        return $data;
    }

    /**
     * Test API connection
     */
    public function testConnection()
    {
        $response = $this->makeRequest('GET', 'api/setup/locations');
        return $response !== null;
    }
}