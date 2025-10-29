<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OkMobilityService
{
    private $baseUrl;
    private $customerCode;
    private $companyCode;
    private $cacheFile;
    private $cacheTime = 3600; // 1 hour cache

    public function __construct()
    {
        $this->baseUrl = config('services.okmobility.url', 'https://ws01.okrentacar.es:60060');
        $this->customerCode = config('services.okmobility.customer_code', '60168');
        $this->companyCode = config('services.okmobility.company_code');
        $this->cacheFile = storage_path('app/cache/okmobility_cache.json');

        if (empty($this->baseUrl) || empty($this->customerCode)) {
            throw new \Exception("Credentials or URL for OK Mobility are not configured correctly.");
        }
    }

    /**
     * Fetches available vehicles based on search criteria.
     * Corresponds to the getMultiplePrices operation.
     */
    public function getVehicles($pickupStationId, $dropoffStationId, $pickupDate, $pickupTime, $dropoffDate, $dropoffTime, $options = [], $groupId = null)
    {
        $pickupDateTime = "{$pickupDate} {$pickupTime}";
        $dropoffDateTime = "{$dropoffDate} {$dropoffTime}";

        // Create cache key
        $cacheKey = "vehicles_{$pickupStationId}_{$dropoffStationId}_{$pickupDateTime}_{$dropoffDateTime}";

        // Check cache first
        $cachedData = $this->getCachedData($cacheKey);
        if ($cachedData) {
            Log::info('OK Mobility: Using cached data for ' . $cacheKey);
            return $cachedData;
        }

        $xmlRequest = '<?xml version="1.0" encoding="utf-8"?>
            <soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
                <soap12:Body>
                    <getMultiplePrices xmlns="http://tempuri.org/">
                        <Value>
                            <companyCode>' . $this->companyCode . '</companyCode>
                            <customerCode>' . $this->customerCode . '</customerCode>
                            <onlyDynamicRate>false</onlyDynamicRate>
                            <PickUpDate>' . $pickupDateTime . '</PickUpDate>
                            <PickUpStation>' . $pickupStationId . '</PickUpStation>
                            <DropOffDate>' . $dropoffDateTime . '</DropOffDate>
                            <DropOffStation>' . $dropoffStationId . '</DropOffStation>';

        if ($groupId) {
            $xmlRequest .= '<groupID>' . $groupId . '</groupID>';
        }

        $xmlRequest .= '</Value>
                    </getMultiplePrices>
                </soap12:Body>
            </soap12:Envelope>';

        try {
            Log::info('OK Mobility API Request (getMultiplePrices): ' . $xmlRequest);

            $response = Http::timeout(120) // Increased timeout to 120 seconds for slow test environment
                ->withHeaders([
                    'Content-Type' => 'application/soap+xml; charset=utf-8',
                    'SOAPAction' => 'http://tempuri.org/getMultiplePrices',
                ])
                ->send('POST', $this->baseUrl . '/getMultiplePrices', [
                    'body' => $xmlRequest
                ]);

            $response->throw();
            Log::info('OK Mobility API Response (getMultiplePrices): ' . $response->body());

            // Cache the response
            $this->setCachedData($cacheKey, $response->body());

            return $response->body();
        } catch (\Exception $e) {
            Log::error('OK Mobility API Error (getMultiplePrices): ' . $e->getMessage());
            if (isset($response)) {
                Log::error('OK Mobility API Error Response (getMultiplePrices): ' . $response->body());
            }
            return null;
        }
    }

    /**
     * Creates a reservation with OK Mobility.
     * Corresponds to the createReservation operation.
     */
    public function makeReservation($reservationData)
    {
        $xmlRequest = '<?xml version="1.0" encoding="utf-8"?>
            <soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
                <soap12:Body>
                    <createReservation xmlns="http://tempuri.org/">
                        <Value>
                            <companyCode>' . $this->companyCode . '</companyCode>
                            <customerCode>' . $this->customerCode . '</customerCode>
                            <messageType>N</messageType>
                            <groupCode>' . $reservationData['group_code'] . '</groupCode>
                            <token>' . $reservationData['token'] . '</token>
                            <PickUp>
                                <Date>' . $reservationData['pickup_date'] . ' ' . $reservationData['pickup_time'] . '</Date>
                                <rentalStation>' . $reservationData['pickup_station_id'] . '</rentalStation>
                            </PickUp>
                            <DropOff>
                                <Date>' . $reservationData['dropoff_date'] . ' ' . $reservationData['dropoff_time'] . '</Date>
                                <rentalStation>' . $reservationData['dropoff_station_id'] . '</rentalStation>
                            </DropOff>
                            <Driver>
                                <Name>' . htmlspecialchars($reservationData['driver_name']) . '</Name>
                                <Surname>' . htmlspecialchars($reservationData['driver_surname'] ?? '') . '</Surname>
                                <EMail>' . htmlspecialchars($reservationData['driver_email']) . '</EMail>
                                <Phone>' . htmlspecialchars($reservationData['driver_phone']) . '</Phone>
                                <Address>' . htmlspecialchars($reservationData['driver_address'] ?? '') . '</Address>
                                <City>' . htmlspecialchars($reservationData['driver_city'] ?? '') . '</City>
                                <PostalCode>' . htmlspecialchars($reservationData['driver_postal_code'] ?? '') . '</PostalCode>
                                <Country>' . htmlspecialchars($reservationData['driver_country'] ?? '') . '</Country>
                                <DriverAge>' . ($reservationData['driver_age'] ?? 25) . '</DriverAge>
                                <IDNumber>' . htmlspecialchars($reservationData['driver_license_number'] ?? '') . '</IDNumber>
                            </Driver>';

        // Add optional extras if provided
        if (isset($reservationData['extras']) && !empty($reservationData['extras'])) {
            $xmlRequest .= '<Extras>';
            foreach ($reservationData['extras'] as $extra) {
                $xmlRequest .= '<Extra><ExtraID>' . $extra['id'] . '</ExtraID><Units>' . ($extra['quantity'] ?? 1) . '</Units></Extra>';
            }
            $xmlRequest .= '</Extras>';
        }

        $xmlRequest .= '<Remarks>' . htmlspecialchars($reservationData['remarks'] ?? '') . '</Remarks>
                        </Value>
                    </createReservation>
                </soap12:Body>
            </soap12:Envelope>';

        try {
            Log::info('OK Mobility API Request (createReservation): ' . $xmlRequest);

            $response = Http::timeout(120) // Increased timeout to 120 seconds for slow test environment
                ->withHeaders([
                    'Content-Type' => 'application/soap+xml; charset=utf-8',
                    'SOAPAction' => 'http://tempuri.org/createReservation',
                ])
                ->send('POST', $this->baseUrl . '/createReservation', [
                    'body' => $xmlRequest
                ]);

            $response->throw();
            Log::info('OK Mobility API Response (createReservation): ' . $response->body());

            return $response->body();
        } catch (\Exception $e) {
            Log::error('OK Mobility API Error (createReservation): ' . $e->getMessage());
            if (isset($response)) {
                Log::error('OK Mobility API Error Response (createReservation): ' . $response->body());
            }
            return null;
        }
    }

    /**
     * Fetches all available rental stations.
     */
    public function getStations()
    {
        // Check cache first
        $cachedData = $this->getCachedData('stations');
        if ($cachedData) {
            Log::info('OK Mobility: Using cached stations data');
            return $cachedData;
        }

        $xmlRequest = '<?xml version="1.0" encoding="utf-8"?>
            <soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
                <soap12:Body>
                    <getStations xmlns="http://tempuri.org/">
                        <Value>
                            <companyCode>' . $this->companyCode . '</companyCode>
                            <customerCode>' . $this->customerCode . '</customerCode>
                        </Value>
                    </getStations>
                </soap12:Body>
            </soap12:Envelope>';

        try {
            Log::info('OK Mobility API Request (getStations): ' . $xmlRequest);

            $response = Http::timeout(120) // Increased timeout to 120 seconds for slow test environment
                ->withHeaders([
                    'Content-Type' => 'application/soap+xml; charset=utf-8',
                    'SOAPAction' => 'http://tempuri.org/getStations',
                ])
                ->send('POST', $this->baseUrl . '/getStations', [
                    'body' => $xmlRequest
                ]);

            $response->throw();
            Log::info('OK Mobility API Response (getStations): ' . $response->body());

            // Cache the response for longer time (stations don't change often)
            $this->setCachedData('stations', $response->body(), 86400); // 24 hours cache

            return $response->body();
        } catch (\Exception $e) {
            Log::error('OK Mobility API Error (getStations): ' . $e->getMessage());
            if (isset($response)) {
                Log::error('OK Mobility API Error Response (getStations): ' . $response->body());
            }
            return null;
        }
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
        Log::info('OK Mobility cache cleared');
    }
}
