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
     * Fetches OK Mobility group descriptions (cached).
     * Maps GroupCode -> Description.
     */
    public function getGroupDescriptionMap(): array
    {
        $soapXml = '<?xml version="1.0" encoding="utf-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:get="http://www.OKGroup.es/RentaCarWebService/getWSDL">
  <soapenv:Header/>
  <soapenv:Body>
    <get:getGroupsRequest>
      <get:objRequest>
        <get:customerCode>' . $this->customerCode . '</get:customerCode>
      </get:objRequest>
    </get:getGroupsRequest>
  </soapenv:Body>
</soapenv:Envelope>';

        $response = $this->sendRequest('getGroups', 'getGroups', $soapXml, 30060);

        if (!$response) {
            Log::warning('OK Mobility getGroups response was empty.');
            return [];
        }

        libxml_use_internal_errors(true);
        $xmlObject = simplexml_load_string($response);

        if ($xmlObject === false) {
            Log::warning('OK Mobility getGroups XML parse failed.', [
                'errors' => libxml_get_errors(),
            ]);
            libxml_clear_errors();
            return [];
        }

        $xmlObject->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $xmlObject->registerXPathNamespace('get', 'http://www.OKGroup.es/RentaCarWebService/getWSDL');

        $errorCode = (string) ($xmlObject->xpath('//errorCode')[0] ?? $xmlObject->xpath('//get:errorCode')[0] ?? null);
        if ($errorCode && $errorCode !== 'SUCCESS') {
            Log::warning('OK Mobility getGroups returned an error.', [
                'error_code' => $errorCode,
            ]);
        }

        $groups = $xmlObject->xpath('//Group') ?:
            $xmlObject->xpath('//get:Group') ?:
            $xmlObject->xpath('//getGroupsResult//Group');

        if (empty($groups)) {
            Log::warning('OK Mobility getGroups returned no groups.');
            return [];
        }

        $map = [];
        foreach ($groups as $group) {
            $groupData = json_decode(json_encode($group), true);
            $code = $groupData['GroupCode'] ?? $groupData['groupCode'] ?? null;
            $description = $groupData['Description'] ?? $groupData['description'] ?? null;

            if (!$code || !$description) {
                continue;
            }

            $codeKey = strtoupper(trim($code));
            $desc = trim($description);

            if ($desc === '' || $desc === '-') {
                continue;
            }

            $map[$codeKey] = $desc;
        }

        if (empty($map)) {
            Log::warning('OK Mobility getGroups returned no usable descriptions.');
        }

        return $map;
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
        <extendedModel>true</extendedModel>
      </Value>
    </getMultiplePrices>
  </soap:Body>
</soap:Envelope>';

        $soap11Xml = '<?xml version="1.0" encoding="utf-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:get="http://www.OKGroup.es/RentaCarWebService/getWSDL">
   <soapenv:Header/>
   <soapenv:Body>
       <get:getMultiplePricesRequest>
          <get:objRequest>
             <get:customerCode>' . $this->customerCode . '</get:customerCode>
             <get:companyCode>' . $this->companyCode . '</get:companyCode>
             <get:pickUp>
                <get:Date>' . $pickupDateTime . '</get:Date>
                <get:rentalStation>' . $pickupStationId . '</get:rentalStation>
             </get:pickUp>
              <get:dropOff>
                 <get:Date>' . $dropoffDateTime . '</get:Date>
                 <get:rentalStation>' . $dropoffStationId . '</get:rentalStation>
              </get:dropOff>';

        if ($groupId) {
            $soap11Xml .= '<get:groupID>' . $groupId . '</get:groupID>';
        }

        $soap11Xml .= '          <get:extendedModel>true</get:extendedModel>
           </get:objRequest>
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
            if (str_contains($soap12Response, '<VehicleModel>') || str_contains($soap12Response, '<vehicleModel>')) {
                return $soap12Response;
            }
        }

        $soap11Response = $this->sendRequest('getMultiplePrices', 'getMultiplePrices', $soap11Xml);

        return $soap11Response ?: $soap12Response;
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
