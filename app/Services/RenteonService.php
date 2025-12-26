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
                ->timeout(30)
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
     * Note: Vehicle search endpoint not yet available in Renteon API
     * This method is prepared for when the endpoint becomes available
     */
    public function getVehicles($pickupCode, $dropoffCode, $startDate, $startTime, $endDate, $endTime, $options = [])
    {
        $params = array_merge([
            'pickup_location_code' => $pickupCode,
            'dropoff_location_code' => $dropoffCode ?? $pickupCode,
            'start_date' => $startDate,
            'start_time' => $startTime,
            'end_date' => $endDate,
            'end_time' => $endTime,
            'provider_code' => $this->providerCode,
        ], $options);

        // TODO: Update with actual endpoint when Renteon provides vehicle search API
        // For now, return null to indicate no vehicles available
        // The endpoint structure should be something like:
        // return $this->makeRequest('GET', 'api/vehicles/search', $params);

        Log::info('Renteon vehicle search called (endpoint not yet available)', [
            'pickup_code' => $pickupCode,
            'dropoff_code' => $dropoffCode,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        return null;
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
     * Get available vehicles from Renteon and transform to unified format
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
     * Transform vehicle data to unified format
     * This will need to be implemented based on actual vehicle data structure
     */
    private function transformVehicle($vehicle, $pickupCode, $locationLat = null, $locationLng = null, $locationName = null, $rentalDays = 1)
    {
        // Parse SIPP code if available to extract vehicle details
        $sippCode = $vehicle['sipp_code'] ?? $vehicle['acriss_code'] ?? null;
        $parsedSipp = $this->parseSippCode($sippCode);

        $brand = $vehicle['make'] ?? 'Renteon';
        $model = $vehicle['model'] ?? 'Renteon Vehicle';
        $category = $parsedSipp['category'] ?? ($vehicle['category'] ?? 'Unknown');

        return [
            'id' => 'renteon_' . ($vehicle['id'] ?? md5($pickupCode . '_' . ($sippCode ?? 'unknown'))),
            'source' => 'renteon',
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