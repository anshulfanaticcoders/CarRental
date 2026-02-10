<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AdobeCarService
{
    protected $baseUrl;
    protected $username;
    protected $password;
    protected $customerCode;

    public function __construct()
    {
        $this->baseUrl = env('ADOBE_URL', 'https://adobecar.cr:42800');
        $this->username = env('ADOBE_USERNAME', 'Z11338');
        $this->password = env('ADOBE_PASSWORD', '11338');
        $this->customerCode = $this->username; // Using username as customer code
    }

    /**
     * Get the customer code for Adobe
     */
    public function getCustomerCode(): string
    {
        return $this->customerCode;
    }

    /**
     * Authenticates with the Adobe API and returns an access token.
     * The token is cached to avoid repeated login requests.
     *
     * @return string|null The access token, or null on failure.
     */
    protected function getAccessToken(): ?string
    {
        $cachedToken = Cache::get('adobe_api_token');
        if (!empty($cachedToken)) {
            return $cachedToken;
        }

        $response = Http::withOptions(['verify' => false])->post(rtrim($this->baseUrl, '/') . '/Auth/Login', [
            'userName' => $this->username,
            'password' => $this->password,
        ]);

        if ($response->successful() && $response->json('token')) {
            $token = $response->json('token');
            Cache::put('adobe_api_token', $token, 55);
            return $token;
        }

        logger()->error('Failed to get Adobe API access token.', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return null;
    }

    /**
     * Fetches the list of all offices (locations) from the Adobe API.
     *
     * @return array A list of offices, or an empty array on failure.
     */
    public function getOfficeList(): array
    {
        $token = $this->getAccessToken();

        if (!$token) {
            return [];
        }

        $response = Http::withOptions(['verify' => false])
            ->withToken($token)
            ->get(rtrim($this->baseUrl, '/') . '/Offices');

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['data']) && is_array($data['data'])) {
                return $data['data'];
            }
            return is_array($data) ? $data : [];
        }

        logger()->error('Failed to fetch Adobe API office list.', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return [];
    }

    /**
     * Fetches available vehicles for given location and dates.
     *
     * @param array $params Search parameters including pickupoffice, startdate, enddate, etc.
     * @return array Available vehicles data or empty array on failure.
     */
    public function getAvailableVehicles(array $params): array
    {
        logger()->info('Adobe API: Starting vehicle search', ['params' => $params]);

        $token = $this->getAccessToken();

        if (!$token) {
            logger()->error('Adobe API: Failed to get access token for vehicle search.');
            return [];
        }

        logger()->info('Adobe API: Token obtained, making API call');
        logger()->info('Adobe API: URL', ['url' => "{$this->baseUrl}/Client/GetAvailabilityWithPrice"]);

        // Adobe API expects query parameters for GetAvailabilityWithPrice
        $queryParams = [
            'pickupOffice' => $params['pickupOffice'] ?? $params['pickupoffice'] ?? '',
            'returnOffice' => $params['returnOffice'] ?? $params['returnoffice'] ?? $params['pickupOffice'] ?? $params['pickupoffice'] ?? '',
            'startDate' => $params['startDate'] ?? $params['startdate'] ?? '',
            'endDate' => $params['endDate'] ?? $params['enddate'] ?? '',
            'customerCode' => $this->customerCode
        ];

        // Add promotion code if provided
        if (!empty($params['promotionCode'])) {
            $queryParams['promotionCode'] = $params['promotionCode'];
        }

        logger()->info('Adobe API: Query parameters', ['queryParams' => $queryParams]);

        $response = Http::withOptions(['verify' => false])
            ->withToken($token)
            ->get(rtrim($this->baseUrl, '/') . '/Client/GetAvailabilityWithPrice', $queryParams);

        logger()->info('Adobe API: Response received', [
            'status' => $response->status(),
            'successful' => $response->successful(),
            'body_length' => strlen($response->body()),
            'body' => $response->body()
        ]);

        if ($response->successful()) {
            logger()->info('Adobe API: Vehicle search successful');
            $vehicles = $response->json();
            return [
                'result' => true,
                'data' => $vehicles
            ];
        }

        logger()->error('Adobe API: Failed to fetch available vehicles.', [
            'status' => $response->status(),
            'body' => $response->body(),
            'params' => $queryParams
        ]);

        return [];
    }

    /**
     * Creates a mock booking or real booking to get detailed vehicle information or finalize reservation.
     *
     * @param array $params Booking parameters
     * @return array Detailed booking information or empty array on failure.
     */
    public function getVehicleDetails(array $params): array
    {
        $token = $this->getAccessToken();

        if (!$token) {
            return [];
        }

        // Create a mock booking to get detailed vehicle info
        $response = Http::withToken($token)->post(rtrim($this->baseUrl, '/') . '/Booking', $params);

        if ($response->successful()) {
            $bookingData = $response->json();
            if ($bookingData['result'] && isset($bookingData['data']['bookingNumber'])) {
                $bookingNumber = $bookingData['data']['bookingNumber'];
                return $this->getBookingDetails($bookingNumber);
            }
        }

        logger()->error('Failed to create mock booking for Adobe vehicle details.', [
            'status' => $response->status(),
            'body' => $response->body(),
            'params' => $params
        ]);

        return [];
    }

    /**
     * Creates a real booking in the Adobe system.
     *
     * @param array $params Booking parameters (pickupOffice, returnOffice, pickupDate, returnDate, category, customerCode, customerName, flightNumber, comment)
     * @return array API response indicating success or failure
     */
    public function createBooking(array $params): array
    {
        $token = $this->getAccessToken();

        if (!$token) {
            return ['result' => false, 'error' => 'Authentication failed'];
        }

        $response = Http::withToken($token)->post(rtrim($this->baseUrl, '/') . '/Booking', $params);

        if ($response->successful()) {
            return $response->json();
        }

        logger()->error('Failed to create Adobe booking.', [
            'status' => $response->status(),
            'body' => $response->body(),
            'params' => $params
        ]);

        return [
            'result' => false,
            'error' => 'Adobe API Error: ' . ($response->json('error') ?? $response->reason())
        ];
    }

    /**
     * Retrieves booking details which include protections and extras information.
     *
     * @param string $bookingNumber The booking number to retrieve details for
     * @return array Booking details or empty array on failure
     */
    public function getBookingDetails(string $bookingNumber): array
    {
        $token = $this->getAccessToken();

        if (!$token) {
            return [];
        }

        $response = Http::withToken($token)->get(rtrim($this->baseUrl, '/') . '/Booking', [
            'bookingNumber' => $bookingNumber,
            'customerCode' => $this->customerCode
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        logger()->error('Failed to fetch Adobe booking details.', [
            'status' => $response->status(),
            'body' => $response->body(),
            'bookingNumber' => $bookingNumber
        ]);

        return [];
    }

    /**
     * Gets protections and extras information for a specific location and vehicle category.
     *
     * @param string $locationCode Location code
     * @param string $category Vehicle category
     * @param array $dates Date parameters
     * @return array Protections and extras data
     */
    public function getProtectionsAndExtras(string $locationCode, string $category, array $dates): array
    {
        logger()->info('Adobe API: Getting protections and extras', [
            'location' => $locationCode,
            'category' => $category,
            'dates' => $dates
        ]);

        $token = $this->getAccessToken();

        if (!$token) {
            logger()->error('Adobe API: Failed to get access token for protections/extras.');
            return ['protections' => [], 'extras' => []];
        }

        // Adobe API GetCategoryWithFare parameters
        $queryParams = [
            'pickupOffice' => $locationCode,
            'returnOffice' => $locationCode, // Required field
            'category' => $category,
            'startDate' => $dates['startDate'] ?? $dates['startdate'] ?? '',
            'endDate' => $dates['endDate'] ?? $dates['enddate'] ?? '',
            'customerCode' => $this->customerCode,
            'idioma' => 'en' // Required language field
        ];

        logger()->info('Adobe API: Calling GetCategoryWithFare', ['queryParams' => $queryParams]);

        $response = Http::withToken($token)->get(rtrim($this->baseUrl, '/') . '/Client/GetCategoryWithFare', $queryParams);

        if ($response->successful()) {
            logger()->info('Adobe API: GetCategoryWithFare successful');
            $data = $response->json();

            // Log the complete API response for debugging
            logger()->info('Adobe API: Complete GetCategoryWithFare response', [
                'status' => $response->status(),
                'data_structure' => $data,
                'items_count' => isset($data['items']) ? count($data['items']) : 0
            ]);

            if (isset($data['items']) && is_array($data['items'])) {
                $protections = [];
                $extras = [];

                foreach ($data['items'] as $index => $item) {
                    // Log each item to understand the structure
                    logger()->info("Adobe API: Item {$index}", [
                        'type' => $item['type'] ?? 'unknown',
                        'code' => $item['code'] ?? 'no_code',
                        'name' => $item['name'] ?? 'no_name',
                        'included' => $item['included'] ?? false,
                        'required' => $item['required'] ?? false,
                        'total' => $item['total'] ?? 0,
                        'full_item' => $item
                    ]);

                    // Adobe items have 'type' field - separate protections from extras
                    // 'Proteccion' = Protections, 'Adicionales' = Extras/Add-ons, 'BaseRate' = Base Rate
                    if (isset($item['type']) && ($item['type'] === 'Proteccion' || $item['type'] === 'protection')) {
                        // Add Adobe-specific fields
                        $item['displayName'] = $item['name'] ?? $item['code'] ?? 'Protection';
                        $item['displayDescription'] = $item['description'] ?? '';
                        $item['price'] = $item['total'] ?? 0;
                        $item['included'] = $item['included'] ?? false;
                        $item['required'] = $item['required'] ?? false;

                        // Adobe business logic:
                        // - required=true items MUST be selected (like PLI - Liability Protection)
                        // - required=false items are optional (like LDW, SPP)
                        // - included=true items (if any exist) are pre-selected and can't be deselected
                        $item['selected'] = ($item['required'] ?? false);

                        $protections[] = $item;
                    } elseif (isset($item['type']) && ($item['type'] === 'Adicionales' || $item['type'] === 'BaseRate')) {
                        // Skip BaseRate as it's already handled in the main vehicle data
                        if ($item['type'] !== 'BaseRate') {
                            // Add Adobe-specific fields
                            $item['displayName'] = $item['name'] ?? $item['code'] ?? 'Extra';
                            $item['displayDescription'] = $item['description'] ?? '';
                            $item['price'] = $item['total'] ?? 0;
                            $item['included'] = $item['included'] ?? false;
                            $item['required'] = $item['required'] ?? false;

                            // Adobe business logic for extras:
                            // - Most extras are optional (required=false)
                            // - If any extra is required, auto-select it
                            // - If any extra is included, auto-select it and make it non-optional
                            $item['selected'] = ($item['required'] ?? false);

                            $extras[] = $item;
                        }
                    }
                }

                logger()->info('Adobe API: Processed protections and extras', [
                    'protections_count' => count($protections),
                    'extras_count' => count($extras),
                    'protections' => $protections,
                    'extras' => $extras
                ]);

                return [
                    'protections' => $protections,
                    'extras' => $extras
                ];
            }
        }

        logger()->error('Adobe API: Failed to GetCategoryWithFare', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        return ['protections' => [], 'extras' => []];
    }

    /**
     * Updates the abobecardetails.json file with fresh vehicle data for a location.
     *
     * @param string $locationCode Location code
     * @param array $vehicleData Vehicle data to store
     * @return bool Success status
     */
    public function updateAdobeDetailsJson(string $locationCode, array $vehicleData): bool
    {
        try {
            $filePath = public_path('abobecardetails.json');
            $currentData = [];

            // Load existing data if file exists
            if (file_exists($filePath)) {
                $jsonContent = file_get_contents($filePath);
                if ($jsonContent !== false) {
                    $currentData = json_decode($jsonContent, true) ?: [];
                }
            }

            // Update data for this location
            $currentData[$locationCode] = array_merge($vehicleData, [
                'location' => [
                    'code' => $locationCode,
                    'last_updated' => now()->toISOString()
                ]
            ]);

            // Save updated data
            $jsonContent = json_encode($currentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $result = file_put_contents($filePath, $jsonContent);

            if ($result !== false) {
                logger()->info("Adobe details JSON updated successfully for location: {$locationCode}");
                return true;
            }

            logger()->error("Failed to write Adobe details JSON for location: {$locationCode}");
            return false;

        } catch (\Exception $e) {
            logger()->error("Error updating Adobe details JSON", [
                'location' => $locationCode,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Gets cached vehicle data for a location from abobecardetails.json.
     *
     * @param string $locationCode Location code
     * @param int $cacheTTL Cache time-to-live in minutes (default: 60)
     * @return array|null Cached vehicle data or null if not found/expired
     */
    public function getCachedVehicleData(string $locationCode, int $cacheTTL = 60): ?array
    {
        $filePath = public_path('abobecardetails.json');

        if (!file_exists($filePath)) {
            return null;
        }

        try {
            $jsonContent = file_get_contents($filePath);
            if ($jsonContent === false) {
                return null;
            }

            $data = json_decode($jsonContent, true);
            if (!isset($data[$locationCode])) {
                return null;
            }

            // Check if cache is expired
            $lastUpdated = $data[$locationCode]['location']['last_updated'] ?? null;
            if ($lastUpdated) {
                $lastUpdatedTime = \Carbon\Carbon::parse($lastUpdated);
                if ($lastUpdatedTime->diffInMinutes(now()) > $cacheTTL) {
                    return null; // Cache expired
                }
            }

            return $data[$locationCode];

        } catch (\Exception $e) {
            logger()->error("Error reading Adobe cached vehicle data", [
                'location' => $locationCode,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
