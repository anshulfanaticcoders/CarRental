<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AdobeCarService
{
    protected $baseUrl;
    protected $username;
    protected $password;

    public function __construct()
    {
        $this->baseUrl = env('ADOBE_URL');
        $this->username = env('ADOBE_USERNAME');
        $this->password = env('ADOBE_PASSWORD');
    }

    /**
     * Authenticates with the Adobe API and returns an access token.
     * The token is cached to avoid repeated login requests.
     *
     * @return string|null The access token, or null on failure.
     */
    protected function getAccessToken(): ?string
    {
        return Cache::remember('adobe_api_token', 55, function () { // Cache for 55 minutes
            $response = Http::post("{$this->baseUrl}/Auth/Login", [
                'userName' => $this->username,
                'password' => $this->password,
            ]);

            if ($response->successful() && $response->json('token')) {
                return $response->json('token');
            }

            logger()->error('Failed to get Adobe API access token.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        });
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

        $response = Http::withToken($token)->get("{$this->baseUrl}/Offices");

        if ($response->successful()) {
            return $response->json();
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
            'pickupoffice' => $params['pickupoffice'] ?? '',
            'returnoffice' => $params['returnoffice'] ?? $params['pickupoffice'] ?? '',
            'startdate' => $params['startdate'] ?? '',
            'enddate' => $params['enddate'] ?? '',
            'customerCode' => 'PRUEBA' // Default customer code as per Adobe documentation
        ];

        // Add promotion code if provided
        if (!empty($params['promotionCode'])) {
            $queryParams['promotionCode'] = $params['promotionCode'];
        }

        logger()->info('Adobe API: Query parameters', ['queryParams' => $queryParams]);

        $response = Http::withToken($token)->get("{$this->baseUrl}/Client/GetAvailabilityWithPrice", $queryParams);

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
     * Creates a mock booking to get detailed vehicle information including protections and extras.
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
        $response = Http::withToken($token)->post("{$this->baseUrl}/Booking", $params);

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

        $response = Http::withToken($token)->get("{$this->baseUrl}/Booking", [
            'bookingNumber' => $bookingNumber,
            'customerCode' => 'PRUEBA' // Default customer code for details lookup
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
            'pickupoffice' => $locationCode,
            'category' => $category,
            'startdate' => $dates['startdate'],
            'enddate' => $dates['enddate'],
            'customerCode' => 'PRUEBA' // Default customer code as per Adobe documentation
        ];

        logger()->info('Adobe API: Calling GetCategoryWithFare', ['queryParams' => $queryParams]);

        $response = Http::withToken($token)->get("{$this->baseUrl}/Client/GetCategoryWithFare", $queryParams);

        if ($response->successful()) {
            logger()->info('Adobe API: GetCategoryWithFare successful');
            $data = $response->json();

            if (isset($data['items']) && is_array($data['items'])) {
                $protections = [];
                $extras = [];

                foreach ($data['items'] as $item) {
                    // Adobe items have 'type' field - separate protections from extras
                    if (isset($item['type']) && ($item['type'] === 'Proteccion' || $item['type'] === 'protection')) {
                        $protections[] = $item;
                    } else {
                        $extras[] = $item;
                    }
                }

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
