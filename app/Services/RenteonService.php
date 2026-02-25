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
    private $allowedProviders;
    private array $defaultPricelistCodes = [];
    private array $providerDetailsCache = [];

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
        $this->allowedProviders = $this->parseAllowedProviders($config['allowed_providers'] ?? null);
        $this->defaultPricelistCodes = $this->parsePricelistCodes($config['pricelist_codes'] ?? null);
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
     * Uses /api/bookings/availability (per Renteon docs)
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

        if (!$this->isProviderAllowed($provider)) {
            Log::warning('Renteon provider not allowed', [
                'provider' => $provider,
                'allowed_providers' => $this->getAllowedProviderCodes(),
            ]);
            return [];
        }

        $pickupDateTime = $this->formatLocalDateTime($startDate, $startTime);
        $dropoffDateTime = $this->formatLocalDateTime($endDate, $endTime);

        if (!$pickupDateTime || !$dropoffDateTime) {
            Log::warning('Renteon availability missing dates', [
                'pickup_date' => $startDate,
                'pickup_time' => $startTime,
                'dropoff_date' => $endDate,
                'dropoff_time' => $endTime,
            ]);
            return [];
        }

        $prepaid = array_key_exists('prepaid', $options) ? (bool) $options['prepaid'] : false;
        $includeOnRequest = array_key_exists('include_on_request', $options)
            ? (bool) $options['include_on_request']
            : true;

        $pricelistCodes = $options['pricelist_codes'] ?? $this->defaultPricelistCodes;
        if (empty($pricelistCodes)) {
            $pricelistCodes = $this->resolvePricelistCodesForProvider($provider, $prepaid);
            if (!empty($pricelistCodes)) {
                Log::info('Renteon: Using provider pricelist codes', [
                    'provider' => $provider,
                    'prepaid' => $prepaid,
                    'pricelist_codes' => $pricelistCodes,
                ]);
            } else {
                Log::info('Renteon: No provider pricelist codes available', [
                    'provider' => $provider,
                    'prepaid' => $prepaid,
                ]);
            }
        }

        $data = [
            'Prepaid' => $prepaid,
            'IncludeOnRequest' => $includeOnRequest,
            'PickupLocation' => $pickupCode,
            'DropOffLocation' => $dropoffCode ?? $pickupCode,
            'PickupDate' => $pickupDateTime,
            'DropOffDate' => $dropoffDateTime,
            'Currency' => $options['currency'] ?? 'EUR',
            'Providers' => [
                array_filter([
                    'Code' => $provider,
                    'PricelistCodes' => !empty($pricelistCodes) ? $pricelistCodes : null,
                    'PickupOfficeIds' => $options['pickup_office_ids'] ?? null,
                    'DropOffOfficeIds' => $options['dropoff_office_ids'] ?? null,
                    'PromoCode' => $options['promo_code'] ?? null,
                ], static fn($value) => $value !== null),
            ],
        ];

        if (!empty($options['car_categories']) && is_array($options['car_categories'])) {
            $data['CarCategories'] = $options['car_categories'];
        }

        if (!empty($options['driver_age'])) {
            $data['Drivers'] = [[
                'DriverAge' => (int) $options['driver_age'],
            ]];
        }

        if (array_key_exists('has_delivery', $options)) {
            $data['HasDelivery'] = (bool) $options['has_delivery'];
        }

        if (array_key_exists('has_collection', $options)) {
            $data['HasCollection'] = (bool) $options['has_collection'];
        }

        Log::info('Renteon search API call', [
            'provider' => $provider,
            'pickup_code' => $pickupCode,
            'dropoff_code' => $dropoffCode ?? $pickupCode,
            'pickup_date' => $pickupDateTime,
            'dropoff_date' => $dropoffDateTime,
            'prepaid' => $prepaid,
            'include_on_request' => $includeOnRequest,
        ]);

        $response = $this->makeRequest('POST', 'api/bookings/availability', $data);

        if (is_array($response) && !empty($response)) {
            $vehicles = $response;
            if (array_key_exists('Vehicles', $response) && is_array($response['Vehicles'])) {
                $vehicles = $response['Vehicles'];
            }

            $firstVehicle = $vehicles[0] ?? null;
            if (is_array($firstVehicle)) {
                Log::info('Renteon search response summary', [
                    'total' => is_array($vehicles) ? count($vehicles) : 0,
                    'first_vehicle_keys' => array_keys($firstVehicle),
                ]);
            }
        }

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
            if (!empty($this->allowedProviders)) {
                $providerCodes = $this->getAllowedProviderCodes();
                Log::info('Renteon: Using allowlisted providers for multi-provider search', [
                    'providers' => $providerCodes,
                ]);
            } else {
                Log::info('Renteon: Multi-provider search skipped (no allowlist configured).');
                return [];
            }
        } else {
            $providerCodes = $this->filterAllowedProviderCodes($providerCodes);
        }

        if (empty($providerCodes)) {
            Log::warning('Renteon: No providers available after allowlist filtering');
            return [];
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

    private function getProviderDetailsCached(string $providerCode): ?array
    {
        $key = $this->normalizeProviderCode($providerCode);
        if (array_key_exists($key, $this->providerDetailsCache)) {
            return $this->providerDetailsCache[$key];
        }
        $details = $this->getProviderDetails($providerCode);
        $this->providerDetailsCache[$key] = is_array($details) ? $details : null;
        return $this->providerDetailsCache[$key];
    }

    private function resolveOfficeFromProvider(string $providerCode, ?string $officeId): ?array
    {
        if (!$officeId) {
            return null;
        }
        $details = $this->getProviderDetailsCached($providerCode);
        if (!$details || empty($details['Offices']) || !is_array($details['Offices'])) {
            return null;
        }
        foreach ($details['Offices'] as $office) {
            if (!is_array($office)) {
                continue;
            }
            if ((string) ($office['OfficeId'] ?? '') === (string) $officeId) {
                return $office;
            }
        }
        return null;
    }

    private function resolveCarCategoryDetails(string $providerCode, ?string $sippCode, array $vehicle = []): ?array
    {
        $candidates = [];
        $values = [
            $sippCode,
            $vehicle['CarCategory'] ?? null,
            $vehicle['CategoryCode'] ?? null,
            $vehicle['sipp_code'] ?? null,
            $vehicle['acriss_code'] ?? null,
        ];
        foreach ($values as $value) {
            $text = strtoupper(trim((string) ($value ?? '')));
            if ($text !== '') {
                $candidates[] = $text;
            }
        }
        $candidates = array_values(array_unique($candidates));
        if (empty($candidates)) {
            return null;
        }
        $details = $this->getProviderDetailsCached($providerCode);
        if (!$details) {
            return null;
        }
        $categoryPools = [];
        if (!empty($details['Connectors']) && is_array($details['Connectors'])) {
            foreach ($details['Connectors'] as $connector) {
                if (!is_array($connector) || empty($connector['CarCategories']) || !is_array($connector['CarCategories'])) {
                    continue;
                }
                $categoryPools[] = $connector['CarCategories'];
            }
        }
        if (!empty($details['CarCategories']) && is_array($details['CarCategories'])) {
            $categoryPools[] = $details['CarCategories'];
        }
        if (empty($categoryPools)) {
            return null;
        }
        foreach ($categoryPools as $categories) {
            foreach ($categories as $category) {
                if (!is_array($category)) {
                    continue;
                }
                $categoryCodes = [];
                foreach (['SIPP', 'Code', 'CarCategorySIPP', 'CarCategoryGroup', 'CarCategoryId', 'Id', 'Title', 'Name'] as $key) {
                    $value = $category[$key] ?? null;
                    $text = strtoupper(trim((string) ($value ?? '')));
                    if ($text !== '') {
                        $categoryCodes[] = $text;
                    }
                }
                foreach ($candidates as $needle) {
                    if (in_array($needle, $categoryCodes, true)) {
                        return $category;
                    }
                }
            }
        }
        return null;
    }

    private function resolvePricelistCodesForProvider(string $providerCode, bool $prepaid): array
    {
        $details = $this->getProviderDetailsCached($providerCode);
        if (!$details) {
            return [];
        }

        $pricelists = $details['Pricelists'] ?? $details['pricelists'] ?? null;
        if (!is_array($pricelists)) {
            return [];
        }

        $codes = [];
        foreach ($pricelists as $pricelist) {
            if (!is_array($pricelist)) {
                continue;
            }

            $code = trim((string) ($pricelist['Code'] ?? $pricelist['code'] ?? ''));
            if ($code === '') {
                continue;
            }

            $isPrepaidRaw = $pricelist['IsPrepaid'] ?? $pricelist['is_prepaid'] ?? $pricelist['Prepaid'] ?? null;
            if ($isPrepaidRaw === null) {
                $codes[] = $code;
                continue;
            }

            $isPrepaid = filter_var($isPrepaidRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($isPrepaid === null) {
                $isPrepaid = (bool) $isPrepaidRaw;
            }

            if ($isPrepaid === $prepaid) {
                $codes[] = $code;
            }
        }

        return array_values(array_unique($codes));
    }

    /**
     * Create booking
     */
    public function createBooking($vehicleData, $customerData, $bookingData)
    {
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

        $prepaidValue = $bookingData['prepaid'] ?? true;
        $prepaid = filter_var($prepaidValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($prepaid === null) {
            $prepaid = (bool) $prepaidValue;
        }

        $payload = [
            'ConnectorId' => $vehicleData['connector_id'] ?? null,
            'CarCategory' => $vehicleData['sipp_code'] ?? null,
            'PickupOfficeId' => $vehicleData['pickup_office_id'] ?? null,
            'DropOffOfficeId' => $vehicleData['dropoff_office_id'] ?? null,
            'PickupDate' => $this->formatLocalDateTime($vehicleData['pickup_date'] ?? '', $vehicleData['pickup_time'] ?? ''),
            'DropOffDate' => $this->formatLocalDateTime($vehicleData['dropoff_date'] ?? '', $vehicleData['dropoff_time'] ?? ''),
            'PricelistId' => $vehicleData['pricelist_id'] ?? null,
            'Currency' => $bookingData['currency'] ?? 'EUR',
            'Prepaid' => $prepaid,
            'PriceDate' => $vehicleData['price_date'] ?? null,
            'PromoCode' => $vehicleData['promo_code'] ?? null,
            'ClientName' => trim(($customerData['first_name'] ?? '') . ' ' . ($customerData['last_name'] ?? '')),
            'ClientEmail' => $customerData['email'] ?? null,
            'ClientPhone' => $customerData['phone'] ?? null,
            'FlightNumber' => $customerData['flight_number'] ?? null,
            'VoucherNumber' => $bookingData['voucher_number'] ?? $bookingData['reference'] ?? null,
        ];

        if (empty($payload['PickupOfficeId']) && !empty($payload['DropOffOfficeId'])) {
            $payload['PickupOfficeId'] = $payload['DropOffOfficeId'];
        }
        if (empty($payload['DropOffOfficeId']) && !empty($payload['PickupOfficeId'])) {
            $payload['DropOffOfficeId'] = $payload['PickupOfficeId'];
        }

        $requiredFields = ['ConnectorId', 'CarCategory', 'PickupOfficeId', 'DropOffOfficeId', 'PickupDate', 'DropOffDate', 'PricelistId', 'Currency'];
        foreach ($requiredFields as $field) {
            $value = $payload[$field] ?? null;
            if ($value === null || $value === '') {
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

        $createResponse = $this->makeRequest('POST', 'api/bookings/create', $payload);
        if (!$createResponse || !is_array($createResponse)) {
            Log::error('Renteon booking create failed', [
                'connector_id' => $payload['ConnectorId'],
                'pickup_office_id' => $payload['PickupOfficeId'],
            ]);
            return null;
        }

        $savePayload = $createResponse;
        $ensureFields = [
            'ClientName' => $payload['ClientName'] ?? null,
            'ClientEmail' => $payload['ClientEmail'] ?? null,
            'ClientPhone' => $payload['ClientPhone'] ?? null,
            'FlightNumber' => $payload['FlightNumber'] ?? null,
            'VoucherNumber' => $payload['VoucherNumber'] ?? null,
        ];
        foreach ($ensureFields as $field => $value) {
            $current = $savePayload[$field] ?? null;
            if ($current === null || (is_string($current) && trim($current) === '')) {
                if ($value !== null && (!is_string($value) || trim($value) !== '')) {
                    $savePayload[$field] = $value;
                }
            }
        }
        if (!isset($savePayload['Services']) && !empty($services)) {
            $savePayload['Services'] = $services;
        }
        if (!isset($savePayload['Drivers']) && !empty($drivers)) {
            $savePayload['Drivers'] = $drivers;
        }

        $saveResponse = $this->makeRequest('POST', 'api/bookings/save', $savePayload);
        if (is_array($saveResponse)) {
            $saveResponse['_renteon_create'] = $createResponse;
            return $saveResponse;
        }

        if (is_array($createResponse)) {
            $createResponse['_renteon_save'] = $saveResponse;
            return $createResponse;
        }

        return $saveResponse ?? $createResponse;
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
            $transformedVehicles[] = $this->transformVehicle($vehicle, $pickupCode, $locationLat, $locationLng, $locationName, $rentalDays, $dropoffCode);
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
            $transformedVehicles[] = $this->transformVehicle($vehicle, $pickupCode, $locationLat, $locationLng, $locationName, $rentalDays, $dropoffCode);
        }

        return $transformedVehicles;
    }

    /**
     * Transform vehicle data to unified format
     */
    private function transformVehicle($vehicle, $pickupCode, $locationLat = null, $locationLng = null, $locationName = null, $rentalDays = 1, $dropoffCode = null)
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

        $categoryDetails = $this->resolveCarCategoryDetails($providerCode, $sippCode, $vehicle);
        if ($depositAmount === null && is_array($categoryDetails)) {
            $depositAmount = $categoryDetails['DepositAmount'] ?? $depositAmount;
            $depositCurrency = $categoryDetails['DepositCurrency'] ?? $depositCurrency;
        }

        $totalAmount = isset($vehicle['Amount']) ? (float) $vehicle['Amount'] : (float) ($vehicle['daily_rate'] ?? 0) * $rentalDays;
        $netAmount = isset($vehicle['NetAmount']) ? (float) $vehicle['NetAmount'] : null;
        $vatAmount = isset($vehicle['VatAmount']) ? (float) $vehicle['VatAmount'] : null;
        $pricePerDay = $rentalDays > 0 ? $totalAmount / $rentalDays : $totalAmount;
        $currency = $vehicle['Currency'] ?? $vehicle['currency'] ?? 'EUR';

        $pickupOfficeId = $vehicle['PickupOfficeId']
            ?? $vehicle['PickupOfficeID']
            ?? $vehicle['pickup_office_id']
            ?? $vehicle['pickupOfficeId']
            ?? null;
        $dropoffOfficeId = $vehicle['DropOffOfficeId']
            ?? $vehicle['DropoffOfficeId']
            ?? $vehicle['DropOffOfficeID']
            ?? $vehicle['DropoffOfficeID']
            ?? $vehicle['dropoff_office_id']
            ?? $vehicle['dropoffOfficeId']
            ?? null;
        $pickupOfficeDetails = $this->normalizeOfficeDetails($vehicle['PickupOffice'] ?? null, $vehicle, 'Pickup');
        $dropoffOfficeDetails = $this->normalizeOfficeDetails($vehicle['DropOffOffice'] ?? null, $vehicle, 'DropOff');
        if (!$pickupOfficeDetails && $pickupOfficeId) {
            $pickupOfficeDetails = $this->normalizeOfficeDetails(
                $this->resolveOfficeFromProvider($providerCode, (string) $pickupOfficeId),
                $vehicle,
                'Pickup'
            );
        }
        if (!$dropoffOfficeDetails && $dropoffOfficeId) {
            $dropoffOfficeDetails = $this->normalizeOfficeDetails(
                $this->resolveOfficeFromProvider($providerCode, (string) $dropoffOfficeId),
                $vehicle,
                'DropOff'
            );
        }

        $vehicleId = $vehicle['id'] ?? md5($providerCode . '_' . $pickupCode . '_' . ($sippCode ?? 'unknown') . '_' . ($pickupOfficeId ?? ''));

        $prepaid = $vehicle['Prepaid'] ?? $vehicle['IsPrepaid'] ?? $vehicle['prepaid'] ?? false;

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
            'provider_return_id' => $dropoffCode ?? $pickupCode,
            'provider_pickup_office_id' => $pickupOfficeId,
            'provider_dropoff_office_id' => $dropoffOfficeId,
            'pickup_office' => $pickupOfficeDetails,
            'dropoff_office' => $dropoffOfficeDetails,
            'connector_id' => $vehicle['ConnectorId']
                ?? $vehicle['ConnectorID']
                ?? $vehicle['connector_id']
                ?? $vehicle['connectorId']
                ?? null,
            'price_date' => $vehicle['PriceDate']
                ?? $vehicle['PriceDateTime']
                ?? $vehicle['price_date']
                ?? $vehicle['priceDate']
                ?? null,
            'pricelist_id' => $vehicle['PricelistId']
                ?? $vehicle['PriceListId']
                ?? $vehicle['PricelistID']
                ?? $vehicle['PriceListID']
                ?? $vehicle['pricelist_id']
                ?? $vehicle['price_list_id']
                ?? null,
            'pricelist_code' => $vehicle['PricelistCode']
                ?? $vehicle['PriceListCode']
                ?? $vehicle['pricelist_code']
                ?? null,
            'is_on_request' => $vehicle['IsOnRequest'] ?? false,
            'prepaid' => filter_var($prepaid, FILTER_VALIDATE_BOOLEAN),
            'provider_gross_amount' => $totalAmount,
            'provider_net_amount' => $netAmount,
            'provider_vat_amount' => $vatAmount,
            'extras' => $this->mapAvailableServices($vehicle['AvailableServices'] ?? [], $rentalDays),
            'features' => $vehicle['features'] ?? [],
            'airConditioning' => $parsedSipp['air_conditioning'] ?? false,
            'benefits' => [
                'minimum_driver_age' => (int) ($vehicle['MinimumDriverAge']
                    ?? ($categoryDetails['MinimumDriverAge'] ?? null)
                    ?? ($vehicle['YoungDriverAgeFrom'] ?? 21)),
                'maximum_driver_age' => $vehicle['MaximumDriverAge']
                    ?? ($categoryDetails['MaximumDriverAge'] ?? null)
                    ?? $vehicle['SeniorDriverAgeTo']
                    ?? null,
                'young_driver_age_from' => $vehicle['YoungDriverAgeFrom'] ?? null,
                'young_driver_age_to' => $vehicle['YoungDriverAgeTo'] ?? null,
                'senior_driver_age_from' => $vehicle['SeniorDriverAgeFrom'] ?? null,
                'senior_driver_age_to' => $vehicle['SeniorDriverAgeTo'] ?? null,
                'deposit_amount' => $depositAmount,
                'deposit_currency' => $depositCurrency,
                'excess_amount' => $vehicle['ExcessAmount'] ?? ($categoryDetails['FranchiseAmount'] ?? null),
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

    private function parseAllowedProviders($allowedProviders): array
    {
        if (empty($allowedProviders)) {
            return [];
        }

        $providers = [];
        $list = is_array($allowedProviders) ? $allowedProviders : explode(',', (string) $allowedProviders);

        foreach ($list as $provider) {
            $provider = trim((string) $provider);
            if ($provider === '') {
                continue;
            }
            $providers[$this->normalizeProviderCode($provider)] = $provider;
        }

        return $providers;
    }

    private function parsePricelistCodes($pricelistCodes): array
    {
        if (empty($pricelistCodes)) {
            return [];
        }

        $codes = is_array($pricelistCodes) ? $pricelistCodes : explode(',', (string) $pricelistCodes);
        $result = [];
        foreach ($codes as $code) {
            $code = trim((string) $code);
            if ($code === '') {
                continue;
            }
            $result[] = $code;
        }

        return $result;
    }

    private function normalizeProviderCode($providerCode): string
    {
        return strtolower(trim((string) $providerCode));
    }

    private function isProviderAllowed($providerCode): bool
    {
        if (empty($this->allowedProviders)) {
            return true;
        }

        $normalized = $this->normalizeProviderCode($providerCode);
        return $normalized !== '' && isset($this->allowedProviders[$normalized]);
    }

    private function getAllowedProviderCodes(): array
    {
        return array_values($this->allowedProviders);
    }

    private function filterAllowedProviderCodes(array $providerCodes): array
    {
        if (empty($this->allowedProviders)) {
            return $providerCodes;
        }

        return array_values(array_filter($providerCodes, function ($providerCode) {
            return $this->isProviderAllowed($providerCode);
        }));
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
        if ($date === '') {
            return null;
        }
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

            $code = $service['AdditionalCode'] ?? $service['Code'] ?? null;
            $name = $service['AdditionalName'] ?? $service['Name'] ?? 'Extra';

            return [
                'id' => $service['ServiceId'] ?? null,
                'service_id' => $service['ServiceId'] ?? null,
                'code' => $code,
                'name' => $name,
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

    private function normalizeOfficeDetails($office, array $vehicle, string $prefix): ?array
    {
        if (is_array($office)) {
            return [
                'office_id' => $office['OfficeId'] ?? null,
                'office_code' => $office['OfficeCode'] ?? null,
                'location_code' => $office['LocationCode'] ?? null,
                'name' => $office['Name'] ?? $office['LocationName'] ?? null,
                'address' => $office['Address'] ?? $office['AddressLine'] ?? null,
                'town' => $office['Town'] ?? null,
                'postal_code' => $office['PostalCode'] ?? null,
                'latitude' => $office['Latitude'] ?? null,
                'longitude' => $office['Longitude'] ?? null,
                'email' => $office['Email'] ?? null,
                'phone' => $office['Tel'] ?? $office['Phone'] ?? null,
                'pickup_instructions' => $office['OfficePickupInstructions'] ?? null,
                'dropoff_instructions' => $office['OfficeDropOffInstructions'] ?? null,
                'location_type' => $office['LocationType'] ?? null,
            ];
        }

        $address = $vehicle[$prefix . 'OfficeAddress'] ?? $vehicle[$prefix . 'Address'] ?? null;
        $town = $vehicle[$prefix . 'OfficeTown'] ?? $vehicle[$prefix . 'Town'] ?? null;
        $postalCode = $vehicle[$prefix . 'OfficePostalCode'] ?? $vehicle[$prefix . 'PostalCode'] ?? null;
        $phone = $vehicle[$prefix . 'OfficePhone'] ?? $vehicle[$prefix . 'Phone'] ?? null;
        $email = $vehicle[$prefix . 'OfficeEmail'] ?? $vehicle[$prefix . 'Email'] ?? null;
        $name = $vehicle[$prefix . 'OfficeName'] ?? null;
        $pickupInstructions = $vehicle[$prefix . 'OfficePickupInstructions'] ?? null;
        $dropoffInstructions = $vehicle[$prefix . 'OfficeDropOffInstructions'] ?? null;

        if (!$address && !$town && !$postalCode && !$phone && !$email && !$name) {
            return null;
        }

        return [
            'office_id' => $vehicle[$prefix . 'OfficeId'] ?? null,
            'office_code' => $vehicle[$prefix . 'OfficeCode'] ?? null,
            'location_code' => $vehicle[$prefix . 'LocationCode'] ?? null,
            'name' => $name,
            'address' => $address,
            'town' => $town,
            'postal_code' => $postalCode,
            'latitude' => $vehicle[$prefix . 'Latitude'] ?? null,
            'longitude' => $vehicle[$prefix . 'Longitude'] ?? null,
            'email' => $email,
            'phone' => $phone,
            'pickup_instructions' => $pickupInstructions,
            'dropoff_instructions' => $dropoffInstructions,
            'location_type' => $vehicle[$prefix . 'LocationType'] ?? null,
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
