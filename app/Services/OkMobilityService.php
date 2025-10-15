<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OkMobilityService
{
    private $baseUrl;
    private $customerCode;
    private $companyCode;

    public function __construct()
    {
        $this->baseUrl = config('services.okmobility.url');
        $this->customerCode = config('services.okmobility.customer_code');
        $this->companyCode = config('services.okmobility.company_code');

        if (empty($this->baseUrl) || empty($this->customerCode) || empty($this->companyCode)) {
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

        $groupXml = '';
        if ($groupId) {
            $groupXml = '<groupID>' . $groupId . '</groupID>';
        }

        $xmlRequest = '<?xml version="1.0" encoding="utf-8"?>
            <soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                <soap:Body>
                    <getMultiplePrices xmlns="http://tempuri.org/">
                        <Value>
                            <companyCode>' . $this->companyCode . '</companyCode>
                            <customerCode>' . $this->customerCode . '</customerCode>
                            <onlyDynamicRate>false</onlyDynamicRate>
                            <PickUpDate>' . $pickupDateTime . '</PickUpDate>
                            <PickUpStation>' . $pickupStationId . '</PickUpStation>
                            <DropOffDate>' . $dropoffDateTime . '</DropOffDate>
                            <DropOffStation>' . $dropoffStationId . '</DropOffStation>
                            ' . $groupXml . '
                        </Value>
                    </getMultiplePrices>
                </soap:Body>
            </soap:Envelope>';

        try {
            Log::info('OK Mobility API Request (getMultiplePrices): ' . $xmlRequest);
            $response = Http::timeout(30)->withHeaders([
                'Content-Type' => 'application/soap+xml; charset=utf-8',
            ])->send('POST', $this->baseUrl . '/getMultiplePrices', ['body' => $xmlRequest]);

            $response->throw();
            Log::info('OK Mobility API Response (getMultiplePrices): ' . $response->body());

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
            <soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                <soap:Body>
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
                                <Name>' . $reservationData['driver_name'] . '</Name>
                                <EMail>' . $reservationData['driver_email'] . '</EMail>
                                <Phone>' . $reservationData['driver_phone'] . '</Phone>
                            </Driver>
                        </Value>
                    </createReservation>
                </soap:Body>
            </soap:Envelope>';

        try {
            Log::info('OK Mobility API Request (createReservation): ' . $xmlRequest);
            $response = Http::timeout(30)->withHeaders([
                'Content-Type' => 'application/soap+xml; charset=utf-8',
            ])->send('POST', $this->baseUrl . '/createReservation', ['body' => $xmlRequest]);

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
        $xmlRequest = '<?xml version="1.0" encoding="utf-8"?>
            <soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                <soap:Body>
                    <getStations xmlns="http://tempuri.org/">
                        <Value>
                            <companyCode>' . $this->companyCode . '</companyCode>
                            <customerCode>' . $this->customerCode . '</customerCode>
                        </Value>
                    </getStations>
                </soap:Body>
            </soap:Envelope>';

        try {
            Log::info('OK Mobility API Request (getStations): ' . $xmlRequest);
            $response = Http::timeout(30)->withHeaders([
                'Content-Type' => 'application/soap+xml; charset=utf-8',
            ])->send('POST', $this->baseUrl . '/getStations', ['body' => $xmlRequest]);

            $response->throw();
            Log::info('OK Mobility API Response (getStations): ' . $response->body());

            return $response->body();
        } catch (\Exception $e) {
            Log::error('OK Mobility API Error (getStations): ' . $e->getMessage());
            if (isset($response)) {
                Log::error('OK Mobility API Error Response (getStations): ' . $response->body());
            }
            return null;
        }
    }
}
