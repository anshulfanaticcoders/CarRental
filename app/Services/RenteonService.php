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
     * Search for available vehicles
     * This will need to be implemented based on the actual vehicle search endpoint
     */
    public function getVehicles($pickupCode, $dropoffCode, $startDate, $endDate, $options = [])
    {
        // This is a placeholder - we'll need to check the actual vehicle search endpoint
        // from the Swagger documentation to implement this correctly

        $params = array_merge([
            'pickup_location_code' => $pickupCode,
            'dropoff_location_code' => $dropoffCode,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'provider_code' => $this->providerCode,
        ], $options);

        // Placeholder endpoint - need to verify actual endpoint from Swagger
        return $this->makeRequest('GET', 'api/vehicles/search', $params);
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
    public function getTransformedVehicles($pickupCode, $dropoffCode, $startDate, $endDate, $startTime = '09:00', $endTime = '09:00', $options = [])
    {
        $vehicles = $this->getVehicles($pickupCode, $dropoffCode, $startDate, $endDate, $options);

        if (!$vehicles) {
            return [];
        }

        // Transform each vehicle to unified format
        $transformedVehicles = [];
        foreach ($vehicles as $vehicle) {
            $transformedVehicles[] = $this->transformVehicle($vehicle);
        }

        return $transformedVehicles;
    }

    /**
     * Transform vehicle data to unified format
     * This will need to be implemented based on actual vehicle data structure
     */
    private function transformVehicle($vehicle)
    {
        return [
            'id' => $vehicle['id'] ?? null,
            'source' => 'renteon',
            'brand' => $vehicle['make'] ?? 'Unknown',
            'model' => $vehicle['model'] ?? 'Unknown',
            'category' => $vehicle['category'] ?? 'Unknown',
            'seating_capacity' => $vehicle['seats'] ?? 4,
            'doors' => $vehicle['doors'] ?? 4,
            'transmission' => $vehicle['transmission'] ?? 'Manual',
            'fuel' => $vehicle['fuel_type'] ?? 'Petrol',
            'price_per_day' => $vehicle['daily_rate'] ?? 0,
            'currency' => $vehicle['currency'] ?? 'EUR',
            'image' => $vehicle['image_url'] ?? '/images/dummyCarImaage.png',
            'provider_pickup_id' => $vehicle['pickup_location_code'] ?? null,
            'features' => $vehicle['features'] ?? [],
            'mileage' => $vehicle['mileage'] ?? 'Limited',
        ];
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