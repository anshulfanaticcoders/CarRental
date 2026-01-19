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
     * Uses /api/bookings/availability
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
        $pickupDateTime = $this->formatLocalDateTime($startDate, $startTime);
        $dropoffDateTime = $this->formatLocalDateTime($endDate, $endTime);

        $driverAge = isset($options['driver_age']) ? (int) $options['driver_age'] : null;
        $drivers = $driverAge ? [['DriverAge' => $driverAge]] : [];

        $data = [
            'Prepaid' => isset($options['prepaid']) ? (bool) $options['prepaid'] : true,
            'IncludeOnRequest' => isset($options['include_on_request']) ? (bool) $options['include_on_request'] : false,
            'Providers' => [
                [
                    'Code' => $provider,
                ]
            ],
            'PickupLocation' => $pickupCode,
            'DropOffLocation' => $dropoffCode ?? $pickupCode,
            'PickupDate' => $pickupDateTime,
            'DropOffDate' => $dropoffDateTime,
            'Currency' => $options['currency'] ?? 'EUR',
            'HasDelivery' => (bool) ($options['has_delivery'] ?? false),
            'HasCollection' => (bool) ($options['has_collection'] ?? false),
        ];

        if (!empty($options['providers'])) {
            $providers = [];
            foreach ($options['providers'] as $providerEntry) {
                if (!is_array($providerEntry) || empty($providerEntry['Code'])) {
                    continue;
                }
                $providers[] = $providerEntry;
            }
            if (!empty($providers)) {
                $data['Providers'] = $providers;
            }
        }

        if (!empty($options['car_categories'])) {
            $data['CarCategories'] = $options['car_categories'];
        }

        if (!empty($drivers)) {
            $data['Drivers'] = $drivers;
        }

        Log::info('Renteon availability API call', [
            'provider' => $provider,
            'pickup_code' => $pickupCode,
            'dropoff_code' => $dropoffCode ?? $pickupCode,
            'pickup_date' => $pickupDateTime,
            'dropoff_date' => $dropoffDateTime,
            'currency' => $data['Currency']
        ]);

        $response = $this->makeRequest('POST', 'api/bookings/availability', $data);

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

        $drivers = [];
        if (!empty($customerData['driver_age'])) {
            $drivers[] = [
                'DriverAge' => (int) $customerData['driver_age'],
                'Name' => $customerData['first_name'] ?? null,
                'Surname' => $customerData['last_name'] ?? null,
            ];
        }

        $services = [];
        if (!empty($bookingData['extras']) && is_array($bookingData['extras'])) {
            foreach ($bookingData['extras'] as $extra) {
                if (empty($extra['id']) || empty($extra['qty'])) {
                    continue;
                }
                $services[] = [
                    'ServiceId' => (int) ($extra['service_id'] ?? $extra['id']),
                    'IsSelected' => (bool) ($extra['isSelected'] ?? true),
                    'Quantity' => (int) $extra['qty'],
                ];
            }
        }

        $payload = [
            'ConnectorId' => $vehicleData['connector_id'] ?? null,
            'CarCategory' => $vehicleData['sipp_code'] ?? null,
            'PickupOfficeId' => $vehicleData['pickup_office_id'] ?? null,
            'DropOffOfficeId' => $vehicleData['dropoff_office_id'] ?? null,
            'PickupDate' => $vehicleData['pickup_date'] ?? null,
            'DropOffDate' => $vehicleData['dropoff_date'] ?? null,
            'PricelistId' => $vehicleData['pricelist_id'] ?? null,
            'Currency' => $bookingData['currency'] ?? 'EUR',
            'Prepaid' => (bool) ($bookingData['prepaid'] ?? true),
            'PriceDate' => $vehicleData['price_date'] ?? null,
            'PromoCode' => $vehicleData['promo_code'] ?? null,
            'ClientName' => trim(($customerData['first_name'] ?? '') . ' ' . ($customerData['last_name'] ?? '')),
            'ClientEmail' => $customerData['email'] ?? null,
            'ClientPhone' => $customerData['phone'] ?? null,
            'FlightNumber' => $customerData['flight_number'] ?? null,
            'VoucherNumber' => $bookingData['voucher_number'] ?? $bookingData['reference'] ?? null,
        ];

        $requiredFields = ['ConnectorId', 'CarCategory', 'PickupOfficeId', 'DropOffOfficeId', 'PickupDate', 'DropOffDate', 'PricelistId', 'Currency'];
        foreach ($requiredFields as $field) {
            if (empty($payload[$field])) {
                Log::error('Renteon booking payload missing required field', [
                    'field' => $field,
                    'payload' => $payload,
                ]);
                return null;
            }
        }

        if (!empty($drivers)) {
            $payload['Drivers'] = $drivers;
        }

        if (!empty($services)) {
            $payload['Services'] = $services;
        }

        Log::info('Renteon booking payload prepared', [
            'connector_id' => $payload['ConnectorId'],
            'car_category' => $payload['CarCategory'],
            'pickup_office_id' => $payload['PickupOfficeId'],
            'dropoff_office_id' => $payload['DropOffOfficeId'],
            'services_count' => count($services),
        ]);

        return $this->makeRequest('POST', 'api/bookings/save', $payload);
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
        $options['driver_age'] = $options['driver_age'] ?? ($options['age'] ?? null);
        $options['currency'] = $options['currency'] ?? 'EUR';
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
        $options['driver_age'] = $options['driver_age'] ?? ($options['age'] ?? null);
        $options['currency'] = $options['currency'] ?? 'EUR';

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
     */
    private function transformVehicle($vehicle, $pickupCode, $locationLat = null, $locationLng = null, $locationName = null, $rentalDays = 1)
    {
        $sippCode = $vehicle['CarCategory'] ?? $vehicle['sipp_code'] ?? $vehicle['acriss_code'] ?? null;
        $parsedSipp = $this->parseSippCode($sippCode);

        $providerCode = $vehicle['provider_code'] ?? $vehicle['Provider'] ?? $this->providerCode;
        $modelName = $vehicle['ModelName'] ?? $vehicle['model'] ?? 'Renteon Vehicle';
        [$brand, $model] = $this->splitModelName($modelName);
        $category = $parsedSipp['category'] ?? ($vehicle['category'] ?? 'Unknown');

        $depositAmount = $vehicle['DepositAmount'] ?? null;
        $depositCurrency = $vehicle['DepositCurrency'] ?? null;
        if ($depositAmount === null && isset($vehicle['Deposit']) && is_array($vehicle['Deposit'])) {
            $depositAmount = $vehicle['Deposit']['Amount'] ?? $depositAmount;
            $depositCurrency = $vehicle['Deposit']['Currency'] ?? $depositCurrency;
        }

        $totalAmount = isset($vehicle['Amount']) ? (float) $vehicle['Amount'] : (float) ($vehicle['daily_rate'] ?? 0) * $rentalDays;
        $pricePerDay = $rentalDays > 0 ? $totalAmount / $rentalDays : $totalAmount;
        $currency = $vehicle['Currency'] ?? $vehicle['currency'] ?? 'EUR';

        $pickupOfficeId = $vehicle['PickupOfficeId'] ?? null;
        $dropoffOfficeId = $vehicle['DropOffOfficeId'] ?? null;

        $vehicleId = $vehicle['id'] ?? md5($providerCode . '_' . $pickupCode . '_' . ($sippCode ?? 'unknown') . '_' . ($pickupOfficeId ?? ''));

        return [
            'id' => 'renteon_' . $providerCode . '_' . $vehicleId,
            'source' => 'renteon',
            'provider_code' => $providerCode,
            'brand' => $brand,
            'model' => $model,
            'category' => $category,
            'sipp_code' => $sippCode,
            'image' => $vehicle['CarModelImageURL'] ?? $vehicle['image_url'] ?? $vehicle['image'] ?? '/images/dummyCarImaage.png',
            'price_per_day' => $pricePerDay,
            'total_price' => $totalAmount,
            'price_per_week' => $pricePerDay * 7,
            'price_per_month' => $pricePerDay * 30,
            'currency' => $currency,
            'transmission' => $parsedSipp['transmission'] ?? ($vehicle['transmission'] ?? 'Manual'),
            'fuel' => $parsedSipp['fuel'] ?? ($vehicle['fuel_type'] ?? 'Petrol'),
            'seating_capacity' => (int) ($vehicle['PassengerCapacity'] ?? $parsedSipp['seating_capacity'] ?? $vehicle['seats'] ?? 4),
            'doors' => (int) ($vehicle['NumberOfDoors'] ?? $parsedSipp['doors'] ?? $vehicle['doors'] ?? 4),
            'luggageLarge' => isset($vehicle['BigBagsCapacity']) ? (int) $vehicle['BigBagsCapacity'] : null,
            'luggageSmall' => isset($vehicle['SmallBagsCapacity']) ? (int) $vehicle['SmallBagsCapacity'] : null,
            'mileage' => $vehicle['mileage'] ?? 'Unlimited',
            'latitude' => (float) ($locationLat ?? 0),
            'longitude' => (float) ($locationLng ?? 0),
            'full_vehicle_address' => $locationName ?? 'Renteon Location',
            'provider_pickup_id' => $pickupCode,
            'provider_pickup_office_id' => $pickupOfficeId,
            'provider_dropoff_office_id' => $dropoffOfficeId,
            'connector_id' => $vehicle['ConnectorId'] ?? null,
            'price_date' => $vehicle['PriceDate'] ?? null,
            'pricelist_id' => $vehicle['PricelistId'] ?? null,
            'pricelist_code' => $vehicle['PricelistCode'] ?? null,
            'is_on_request' => $vehicle['IsOnRequest'] ?? false,
            'prepaid' => $vehicle['Prepaid'] ?? true,
            'extras' => $this->mapAvailableServices($vehicle['AvailableServices'] ?? [], $rentalDays),
            'features' => $vehicle['features'] ?? [],
            'airConditioning' => $parsedSipp['air_conditioning'] ?? false,
            'benefits' => [
                'minimum_driver_age' => (int) ($vehicle['MinimumDriverAge'] ?? $vehicle['YoungDriverAgeFrom'] ?? 21),
                'maximum_driver_age' => $vehicle['MaximumDriverAge'] ?? $vehicle['SeniorDriverAgeTo'] ?? null,
                'young_driver_age_from' => $vehicle['YoungDriverAgeFrom'] ?? null,
                'young_driver_age_to' => $vehicle['YoungDriverAgeTo'] ?? null,
                'senior_driver_age_from' => $vehicle['SeniorDriverAgeFrom'] ?? null,
                'senior_driver_age_to' => $vehicle['SeniorDriverAgeTo'] ?? null,
                'deposit_amount' => $depositAmount,
                'deposit_currency' => $depositCurrency,
                'excess_amount' => $vehicle['ExcessAmount'] ?? null,
                'excess_theft_amount' => $vehicle['ExcessTheftAmount'] ?? null,
                'fuel_policy' => $vehicle['fuel_policy'] ?? 'Full to Full',
            ],
            'products' => [
                [
                    'type' => 'BAS',
                    'total' => (string) $totalAmount,
                    'currency' => $currency,
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

    private function splitModelName($modelName)
    {
        $modelName = trim((string) $modelName);
        if ($modelName === '') {
            return ['Renteon', 'Vehicle'];
        }

        $parts = preg_split('/\s+/', $modelName, 2);
        if (!$parts || count($parts) < 2) {
            return ['Renteon', $modelName];
        }

        return [$parts[0], $parts[1]];
    }

    private function formatLocalDateTime($date, $time)
    {
        $date = trim((string) $date);
        $time = trim((string) $time);
        $time = $time !== '' ? $time : '09:00';

        if (strlen($time) === 5) {
            $time = $time . ':00';
        }

        return $date . 'T' . $time;
    }

    private function mapAvailableServices(array $services, $rentalDays = 1)
    {
        return array_map(function ($service) use ($rentalDays) {
            $amount = isset($service['Amount']) ? (float) $service['Amount'] : 0.0;
            $isOneTime = $service['IsOneTimePayment'] ?? false;
            $dailyRate = $isOneTime ? ($rentalDays > 0 ? $amount / $rentalDays : $amount) : $amount;

            return [
                'id' => $service['ServiceId'] ?? null,
                'service_id' => $service['ServiceId'] ?? null,
                'code' => $service['Code'] ?? null,
                'name' => $service['Name'] ?? $service['AdditionalName'] ?? 'Extra',
                'description' => $service['DescriptionWeb'] ?? $service['Description'] ?? $service['ServiceTypeName'] ?? null,
                'amount' => $amount,
                'price' => $amount,
                'daily_rate' => $dailyRate,
                'max_quantity' => $service['MaximumQuantity'] ?? 1,
                'included' => $service['FreeOfCharge'] ?? false,
                'included_in_price' => $service['IncludedInPriceUnlimited'] ?? false,
                'included_in_price_limited' => $service['IncludedInPriceLimited'] ?? false,
                'prepaid' => $service['Prepaid'] ?? false,
                'is_one_time' => $isOneTime,
                'quantity_included' => $service['QuantityIncluded'] ?? 0,
                'included_per_day' => $service['IncludedPerDurationMeasuringUnit'] ?? false,
                'included_per_contract' => $service['IncludedPerContract'] ?? false,
                'service_group' => $service['ServiceGroupName'] ?? null,
                'service_type' => $service['ServiceTypeName'] ?? null,
            ];
        }, $services);
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