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
        $this->baseUrl = rtrim(config('services.okmobility.url', 'https://ws01.okrentacar.es:60060'), '/');
        $this->customerCode = config('services.okmobility.customer_code', '60168');
        $this->companyCode = config('services.okmobility.company_code', '9937');

        if (empty($this->baseUrl) || empty($this->customerCode)) {
            Log::error("OK Mobility: Credentials or URL not configured correctly.");
        }
    }

    /**
     * Common method to send SOAP requests to OK Mobility.
     */
    private function sendRequest($endpoint, $action, $xmlBody)
    {
        $path = str_starts_with($endpoint, '/') ? $endpoint : '/' . $endpoint;

        // Try the configured URL first, then fallback to port 30060 if it's different
        $urls = [$this->baseUrl . $path];

        if (strpos($this->baseUrl, ':60060') !== false) {
            $urls[] = str_replace(':60060', ':30060', $this->baseUrl) . $path;
        } elseif (strpos($this->baseUrl, ':30060') !== false) {
            $urls[] = str_replace(':30060', ':60060', $this->baseUrl) . $path;
        }

        foreach ($urls as $url) {
            try {
                Log::info("OK Mobility Request TO $url [Action: $action]");

                $response = Http::withHeaders([
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'SOAPAction' => $action,
                    'Accept-Encoding' => 'gzip',
                ])
                    ->timeout(config('services.okmobility.timeout', 12))
                    ->connectTimeout(config('services.okmobility.connect_timeout', 6))
                    ->retry(config('services.okmobility.retry_attempts', 1), config('services.okmobility.retry_delay', 200))
                    ->withOptions([
                        'verify' => false,
                        'curl' => [
                            CURLOPT_ENCODING => '', // Enable all encodings (gzip, deflate)
                        ]
                    ])
                    ->send('POST', $url, [
                        'body' => $xmlBody
                    ]);

                if ($response->successful()) {
                    $body = $response->body();
                    if ($response->header('Content-Encoding') === 'gzip') {
                        $decoded = @gzdecode($body);
                        if ($decoded !== false) {
                            return $decoded;
                        }
                    }
                    return $body;
                }

                Log::warning("OK Mobility attempt failed for $url: " . $response->status() . " - " . $response->reason());
            } catch (\Exception $e) {
                Log::warning("OK Mobility exception for $url: " . $e->getMessage() . " (Type: " . get_class($e) . ")");
            }
        }

        Log::error("OK Mobility: All attempts failed for endpoint $endpoint");
        return null;
    }

    /**
     * Fetches available vehicles based on search criteria.
     * Corresponds to the getMultiplePrices operation.
     */
    public function getVehicles($pickupStationId, $dropoffStationId, $pickupDate, $pickupTime, $dropoffDate, $dropoffTime, $options = [], $groupId = null)
    {
        $pickupDateTime = "{$pickupDate} {$pickupTime}";
        $dropoffDateTime = "{$dropoffDate} {$dropoffTime}";

        $xmlRequest = '<?xml version="1.0" encoding="utf-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:get="http://www.OKGroup.es/RentaCarWebService/getWSDL">
   <soapenv:Header/>
   <soapenv:Body>
       <get:getMultiplePricesRequest>
          <objRequest>
             <customerCode>' . $this->customerCode . '</customerCode>
             <companyCode>' . $this->companyCode . '</companyCode>
             <PickUp>
                <Date>' . $pickupDateTime . '</Date>
                <rentalStation>' . $pickupStationId . '</rentalStation>
             </PickUp>
             <DropOff>
                <Date>' . $dropoffDateTime . '</Date>
                <rentalStation>' . $dropoffStationId . '</rentalStation>
             </DropOff>';


        if ($groupId) {
            $xmlRequest .= '<groupID>' . $groupId . '</groupID>';
        }

        $xmlRequest .= '         </objRequest>
      </get:getMultiplePricesRequest>
   </soapenv:Body>
</soapenv:Envelope>';

        return $this->sendRequest('getMultiplePrices', 'getMultiplePrices', $xmlRequest);
    }

    /**
     * Creates a reservation with OK Mobility.
     * Corresponds to the createReservation operation.
     */
    public function makeReservation($reservationData)
    {
        $xmlRequest = '<?xml version="1.0" encoding="utf-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:get="http://www.OKGroup.es/RentaCarWebService/getWSDL">
   <soapenv:Header/>
   <soapenv:Body>
      <get:createReservationRequest>
         <objRequest>
            <customerCode>' . $this->customerCode . '</customerCode>
            <companyCode>' . $this->companyCode . '</companyCode>
             <MessageType>N</MessageType>
             <Reference>' . htmlspecialchars($reservationData['reference'] ?? '') . '</Reference>
             <token>' . $reservationData['token'] . '</token>
             <groupCode>' . ($reservationData['group_code'] ?? $reservationData['sipp_code'] ?? '') . '</groupCode>
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
                 <EMail>' . htmlspecialchars($reservationData['driver_email']) . '</EMail>
                 <Phone>' . htmlspecialchars($reservationData['driver_phone']) . '</Phone>
                 <Address>' . htmlspecialchars($reservationData['driver_address'] ?? '') . '</Address>
                 <City>' . htmlspecialchars($reservationData['driver_city'] ?? '') . '</City>
                 <Postal_Code>' . htmlspecialchars($reservationData['driver_postal_code'] ?? '') . '</Postal_Code>
                 <Country>' . htmlspecialchars($reservationData['driver_country'] ?? '') . '</Country>
                 <DriverLicenceNumber>' . htmlspecialchars($reservationData['driver_license_number'] ?? '') . '</DriverLicenceNumber>
                 <Date_of_Birth>' . ($reservationData['driver_dob'] ?? '') . '</Date_of_Birth>
             </Driver>';


        // Extras
        if (isset($reservationData['extras']) && !empty($reservationData['extras'])) {
            $extrasString = '';
            foreach ($reservationData['extras'] as $extra) {
                $extrasString .= ($extrasString ? ',' : '') . $extra['id'];
            }
            $xmlRequest .= '<Extras>' . $extrasString . '</Extras>';
        }

        $xmlRequest .= '         </objRequest>
      </get:createReservationRequest>
   </soapenv:Body>
</soapenv:Envelope>';

        return $this->sendRequest('createReservation', 'createReservationOperation', $xmlRequest);
    }

    /**
     * Fetches all available rental stations.
     */
    public function getStations()
    {
        $xmlRequest = '<?xml version="1.0" encoding="utf-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:get="http://www.OKGroup.es/RentaCarWebService/getWSDL">
   <soapenv:Header/>
   <soapenv:Body>
      <get:getStationsRequest>
         <objRequest>
            <customerCode>' . $this->customerCode . '</customerCode>
            <companyCode>' . $this->companyCode . '</companyCode>
         </objRequest>
      </get:getStationsRequest>
   </soapenv:Body>
</soapenv:Envelope>';

        return $this->sendRequest('getStations', 'getStationsOperation', $xmlRequest);
    }

    /**
     * Fetches all available countries.
     */
    public function getCountries()
    {
        $xmlRequest = '<?xml version="1.0" encoding="utf-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:get="http://www.OKGroup.es/RentaCarWebService/getWSDL">
   <soapenv:Header/>
   <soapenv:Body>
      <get:getCountriesRequest>
         <objRequest>
            <customerCode>' . $this->customerCode . '</customerCode>
         </objRequest>
      </get:getCountriesRequest>
   </soapenv:Body>
</soapenv:Envelope>';

        return $this->sendRequest('getCountries', 'getCountriesOperation', $xmlRequest);
    }
}
