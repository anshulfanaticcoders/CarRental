<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OkMobilityService
{
    private $baseUrl;
    private $customerCode;
    private $companyCode;

    private const SOAP12_ACTION_GET_MULTIPLE_PRICES = 'http://tempuri.org/getMultiplePrices';

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
    private function sendRequest($endpoint, $action, $xmlBody, ?int $preferPort = null)
    {
        $path = str_starts_with($endpoint, '/') ? $endpoint : '/' . $endpoint;

        // Try the configured URL first, then fallback to port 30060 if it's different
        $baseUrl = $this->baseUrl;
        $urls = [$baseUrl . $path];
        $alternateUrl = null;

        if (strpos($baseUrl, ':60060') !== false) {
            $alternateUrl = str_replace(':60060', ':30060', $baseUrl) . $path;
        } elseif (strpos($baseUrl, ':30060') !== false) {
            $alternateUrl = str_replace(':30060', ':60060', $baseUrl) . $path;
        }

        if ($alternateUrl) {
            if ($preferPort && str_contains($alternateUrl, ':' . $preferPort)) {
                array_unshift($urls, $alternateUrl);
            } else {
                $urls[] = $alternateUrl;
            }
        }

        $urls = array_values(array_unique($urls));

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
     * Send SOAP 1.2 request to a specific URL.
     */
    private function sendSoap12Request($url, $action, $xmlBody)
    {
        try {
            Log::info("OK Mobility SOAP 1.2 Request TO $url [Action: $action]");

            $response = Http::withHeaders([
                'Content-Type' => 'application/soap+xml; charset=utf-8; action="' . $action . '"',
                'Accept-Encoding' => 'gzip',
            ])
                ->timeout(config('services.okmobility.timeout', 12))
                ->connectTimeout(config('services.okmobility.connect_timeout', 6))
                ->retry(config('services.okmobility.retry_attempts', 1), config('services.okmobility.retry_delay', 200))
                ->withOptions([
                    'verify' => false,
                    'curl' => [
                        CURLOPT_ENCODING => '',
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

            Log::warning("OK Mobility SOAP 1.2 attempt failed for $url: " . $response->status() . " - " . $response->reason());
        } catch (\Exception $e) {
            Log::warning("OK Mobility SOAP 1.2 exception for $url: " . $e->getMessage() . " (Type: " . get_class($e) . ")");
        }

        return null;
    }

    /**
     * Fetches available vehicles based on search criteria.
     * Corresponds to the getMultiplePrices operation.
     */
    public function getVehicles($pickupStationId, $dropoffStationId, $pickupDate, $pickupTime, $dropoffDate, $dropoffTime, $options = [], $groupId = null)
    {
        $pickupDateTime = "{$pickupDate} {$pickupTime}:00";
        $dropoffDateTime = "{$dropoffDate} {$dropoffTime}:00";

        $soap12Xml = '<?xml version="1.0" encoding="utf-8"?>
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
      </Value>
    </getMultiplePrices>
  </soap:Body>
</soap:Envelope>';

        $soap11Xml = '<?xml version="1.0" encoding="utf-8"?>
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
            $soap11Xml .= '<groupID>' . $groupId . '</groupID>';
        }

        $soap11Xml .= '         </objRequest>
      </get:getMultiplePricesRequest>
   </soapenv:Body>
</soapenv:Envelope>';

        $soap12BaseUrl = $this->baseUrl;
        if (strpos($soap12BaseUrl, ':60060') !== false) {
            $soap12BaseUrl = str_replace(':60060', ':30060', $soap12BaseUrl);
        }

        $soap12Url = rtrim($soap12BaseUrl, '/') . '/getMultiplePrices';
        $soap12Response = $this->sendSoap12Request($soap12Url, self::SOAP12_ACTION_GET_MULTIPLE_PRICES, $soap12Xml);

        if ($soap12Response) {
            return $soap12Response;
        }

        return $this->sendRequest('getMultiplePrices', 'getMultiplePrices', $soap11Xml);
    }

    /**
     * Creates a reservation with OK Mobility.
     * Corresponds to the createReservation operation.
     */
    public function makeReservation($reservationData)
    {
        $extrasString = '';
        if (isset($reservationData['extras']) && !empty($reservationData['extras'])) {
            foreach ($reservationData['extras'] as $extra) {
                $extrasString .= ($extrasString ? ',' : '') . $extra['id'];
            }
        }

        $xmlRequest = '<?xml version="1.0" encoding="utf-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:get="http://www.OKGroup.es/RentaCarWebService/getWSDL">
   <soapenv:Body>
      <get:createReservation>
         <get:objRequest>
            <get:customerCode>' . $this->customerCode . '</get:customerCode>
            <get:companyCode>' . $this->companyCode . '</get:companyCode>
            <get:rateCode>' . htmlspecialchars($reservationData['rate_code'] ?? '') . '</get:rateCode>
            <get:MessageType>N</get:MessageType>
            <get:Reference>' . htmlspecialchars($reservationData['reference'] ?? '') . '</get:Reference>
            <get:token>' . htmlspecialchars($reservationData['token'] ?? '') . '</get:token>
            <get:groupCode>' . htmlspecialchars($reservationData['group_code'] ?? $reservationData['sipp_code'] ?? '') . '</get:groupCode>
            <get:PickUp>
               <get:Date>' . $reservationData['pickup_date'] . ' ' . $reservationData['pickup_time'] . '</get:Date>
               <get:rentalStation>' . htmlspecialchars($reservationData['pickup_station_id'] ?? '') . '</get:rentalStation>
               <get:Place>' . htmlspecialchars($reservationData['pickup_place'] ?? '') . '</get:Place>
               <get:Flight>' . htmlspecialchars($reservationData['flight_number'] ?? '') . '</get:Flight>
            </get:PickUp>
            <get:DropOff>
               <get:Date>' . $reservationData['dropoff_date'] . ' ' . $reservationData['dropoff_time'] . '</get:Date>
               <get:rentalStation>' . htmlspecialchars($reservationData['dropoff_station_id'] ?? '') . '</get:rentalStation>
            </get:DropOff>
            <get:Driver>
               <get:Name>' . htmlspecialchars($reservationData['driver_name'] ?? '') . '</get:Name>
               <get:Address>' . htmlspecialchars($reservationData['driver_address'] ?? '') . '</get:Address>
               <get:City>' . htmlspecialchars($reservationData['driver_city'] ?? '') . '</get:City>
               <get:Postal_code>' . htmlspecialchars($reservationData['driver_postal_code'] ?? '') . '</get:Postal_code>
               <get:Phone>' . htmlspecialchars($reservationData['driver_phone'] ?? '') . '</get:Phone>
               <get:DriverLicenceNumber>' . htmlspecialchars($reservationData['driver_license_number'] ?? '') . '</get:DriverLicenceNumber>
               <get:EMail>' . htmlspecialchars($reservationData['driver_email'] ?? '') . '</get:EMail>
               <get:Country>' . htmlspecialchars($reservationData['driver_country'] ?? '') . '</get:Country>
               <get:Date_of_Birth>' . htmlspecialchars($reservationData['driver_dob'] ?? '') . '</get:Date_of_Birth>
            </get:Driver>
            <get:Observations>' . htmlspecialchars($reservationData['remarks'] ?? '') . '</get:Observations>
            <get:Extras>' . htmlspecialchars($extrasString) . '</get:Extras>
         </get:objRequest>
      </get:createReservation>
   </soapenv:Body>
</soapenv:Envelope>';

        return $this->sendRequest('createReservation', 'createReservation', $xmlRequest, 30060);
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
