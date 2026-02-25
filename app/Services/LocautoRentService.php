<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

class LocautoRentService
{
  private $baseUrl;
  private $username;
  private $password;
  private $testMode;

  public function __construct()
  {
    // Use production URL - credentials only work on production, not test environment
    $this->baseUrl = env('LOCAUTO_RENT_PRODUCTION_URL', 'https://api.locautorent.com/NextRentWebService.asmx');
    $this->username = env('LOCAUTO_RENT_USERNAME');
    $this->password = env('LOCAUTO_RENT_PASSWORD');
    $this->testMode = false; // Use production mode
  }

  /**
   * Get available locations from Locauto (using predefined location codes)
   * Note: Locauto doesn't have a location search API - locations are predefined with XML location codes
   */
  public function getLocations(): ?string
  {
    // Return null since we don't need API response for predefined locations
    // The parseLocationResponse method will handle the predefined location list
    Log::info('Using predefined Locauto locations (no API available)');
    return null;
  }

  /**
   * Get vehicle availability for given location and dates
   */
  public function getVehicles($locationCode, $pickupDate, $pickupTime, $returnDate, $returnTime, $age = 35, $options = []): ?string
  {
    try {
      $xmlRequest = $this->buildVehicleAvailabilityRequest($locationCode, $pickupDate, $pickupTime, $returnDate, $returnTime, $age, $options);

      Log::info('LocautoRent Vehicle Search Request: ' . $xmlRequest);

      $response = Http::withHeaders([
        'Content-Type' => 'text/xml; charset=utf-8',
        'SOAPAction' => '"https://nextrent.locautorent.com/OTA_VehAvailRateRS"',
      ])->send('POST', $this->baseUrl, [
            'body' => $xmlRequest,
          ]);

      $response->throw();

      $responseBody = $response->body();
      Log::info('LocautoRent Vehicle Search Response: ' . $responseBody);

      return $responseBody;
    } catch (Exception $e) {
      Log::error('LocautoRent Vehicle Search Error: ' . $e->getMessage());
      if (isset($response)) {
        Log::error('LocautoRent Vehicle Search Error Response: ' . $response->body());
      }
      return null;
    }
  }

  /**
   * Create a reservation
   */
  public function makeReservation($data): ?string
  {
    try {
      $xmlRequest = $this->buildReservationRequest($data);

      Log::info('LocautoRent Reservation Request: ' . $xmlRequest);

      $response = Http::withHeaders([
        'Content-Type' => 'text/xml; charset=utf-8',
        'SOAPAction' => '"https://nextrent.locautorent.com/OTA_VehResRS"',
      ])->send('POST', $this->baseUrl, [
            'body' => $xmlRequest,
          ]);

      $response->throw();

      $responseBody = $response->body();
      Log::info('LocautoRent Reservation Response: ' . $responseBody);

      return $responseBody;
    } catch (Exception $e) {
      Log::error('LocautoRent Reservation Error: ' . $e->getMessage());
      if (isset($response)) {
        Log::error('LocautoRent Reservation Error Response: ' . $response->body());
      }
      return null;
    }
  }

  /**
   * Build location search request XML
   */
  private function buildLocationSearchRequest(): string
  {
    return '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <OTA_VehLocSearchRS xmlns="https://nextrent.locautorent.com">
      <OTA_VehLocSearchRQ EchoToken="' . uniqid() . '" TimeStamp="' . now()->toISOString() . '" Target="' . ($this->testMode ? 'Test' : 'Production') . '" Version="1.0" xmlns="http://www.opentravel.org/OTA/2003/05">
        <POS>
          <Source>
            <RequestorID ID="' . $this->username . '" Password="' . $this->password . '">
              <CompanyName>DPP</CompanyName>
            </RequestorID>
          </Source>
        </POS>
      </OTA_VehLocSearchRQ>
    </OTA_VehLocSearchRS>
  </soap:Body>
</soap:Envelope>';
  }

  /**
   * Build vehicle availability request XML
   */
  private function buildVehicleAvailabilityRequest($locationCode, $pickupDate, $pickupTime, $returnDate, $returnTime, $age, $options): string
  {
    $pickupDateTime = $pickupDate . 'T' . $pickupTime . ':00+02:00';
    $returnDateTime = $returnDate . 'T' . $returnTime . ':00+02:00';
    $returnLocationCode = $options['return_location_code'] ?? $locationCode;

    return '<?xml version="1.0" encoding="utf-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://www.opentravel.org/OTA/2003/05" xmlns:ns2="https://nextrent.locautorent.com">
  <SOAP-ENV:Body>
    <ns2:OTA_VehAvailRateRS>
      <ns1:OTA_VehAvailRateRQ MaxResponses="100" Version="1.0" Target="Production" SequenceNmbr="1" PrimaryLangID="en">
        <ns1:POS>
          <ns1:Source ISOCountry="IT" ISOCurrency="EUR">
            <ns1:RequestorID ID_Context="' . $this->username . '" MessagePassword="' . $this->password . '"/>
          </ns1:Source>
        </ns1:POS>
        <ns1:VehAvailRQCore Status="Available">
          <ns1:VehRentalCore PickUpDateTime="' . $pickupDateTime . '" ReturnDateTime="' . $returnDateTime . '">
            <ns1:PickUpLocation LocationCode="' . $locationCode . '"/>
            <ns1:ReturnLocation LocationCode="' . $returnLocationCode . '"/>
          </ns1:VehRentalCore>
          <ns1:DriverType Age="' . $age . '"/>
        </ns1:VehAvailRQCore>
      </ns1:OTA_VehAvailRateRQ>
    </ns2:OTA_VehAvailRateRS>
  </SOAP-ENV:Body>
</SOAP-ENV:Envelope>';
  }

  /**
   * Build reservation request XML
   */
  private function buildReservationRequest($data): string
  {
    $pickupDateTime = $data['pickup_date'] . 'T' . $data['pickup_time'] . ':00+02:00';
    $returnDateTime = $data['return_date'] . 'T' . $data['return_time'] . ':00+02:00';

    $xml = '<?xml version="1.0" encoding="utf-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="http://www.opentravel.org/OTA/2003/05" xmlns:ns2="https://nextrent.locautorent.com">
  <SOAP-ENV:Body>
    <ns2:OTA_VehResRS>
      <ns1:OTA_VehResRQ EchoToken="' . uniqid() . '" TimeStamp="' . now()->toISOString() . '" Target="Production" Version="1.0">
        <ns1:POS>
          <ns1:Source ISOCountry="IT" ISOCurrency="EUR">
            <ns1:RequestorID ID_Context="' . $this->username . '" MessagePassword="' . $this->password . '"/>
          </ns1:Source>
        </ns1:POS>
        <ns1:VehResRQCore>
          <ns1:VehRentalCore PickUpDateTime="' . $pickupDateTime . '" ReturnDateTime="' . $returnDateTime . '">
            <ns1:PickUpLocation LocationCode="' . $data['pickup_location_code'] . '"/>
            <ns1:ReturnLocation LocationCode="' . $data['return_location_code'] . '"/>
          </ns1:VehRentalCore>
          <ns1:Customer>
            <ns1:Primary>
              <ns1:PersonName>
                <ns1:GivenName>' . htmlspecialchars($data['first_name']) . '</ns1:GivenName>
                <ns1:Surname>' . htmlspecialchars($data['last_name']) . '</ns1:Surname>
              </ns1:PersonName>
            </ns1:Primary>
          </ns1:Customer>
          <ns1:VehPref Code="' . $data['sipp_code'] . '" CodeContext="SIPP"/>';

    // Add special equipment if provided
    if (!empty($data['extras'])) {
      $xml .= '<ns1:SpecialEquipPrefs>';
      foreach ($data['extras'] as $extra) {
        $xml .= '<ns1:SpecialEquipPref Code="' . $extra['code'] . '" Quantity="' . ($extra['quantity'] ?? 1) . '"/>';
      }
      $xml .= '</ns1:SpecialEquipPrefs>';
    }

    $xml .= '</ns1:VehResRQCore>
      </ns1:OTA_VehResRQ>
    </ns2:OTA_VehResRS>
  </SOAP-ENV:Body>
</SOAP-ENV:Envelope>';

    return $xml;
  }

  /**
   * Parse vehicle availability response
   * Response structure: VehAvail > VehAvailCore > Vehicle (with VehMakeModel, PictureURL, etc.)
   */
  public function parseVehicleResponse($xmlResponse): array
  {
    if (empty($xmlResponse)) {
      Log::warning('LocautoRent: Empty XML response received');
      return [];
    }

    try {
      libxml_use_internal_errors(true);
      $xmlObject = simplexml_load_string($xmlResponse);

      if ($xmlObject === false) {
        $errors = libxml_get_errors();
        foreach ($errors as $error) {
          Log::error('LocautoRent XML Parsing Error: ' . $error->message);
        }
        libxml_clear_errors();
        return [];
      }

      $vehicles = [];

      // Register namespaces for XPath
      $xmlObject->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
      $xmlObject->registerXPathNamespace('ota', 'http://www.opentravel.org/OTA/2003/05');
      $xmlObject->registerXPathNamespace('locauto', 'https://nextrent.locautorent.com');

      // Try multiple XPath approaches to find vehicles
      $vehAvails = $xmlObject->xpath('//ota:VehAvail');

      // If standard OTA namespace doesn't work, try wildcard namespace
      if ($vehAvails === false || empty($vehAvails)) {
        $vehAvails = $xmlObject->xpath('//VehAvail');
      }

      // If still not found, try accessing the structure directly
      if ($vehAvails === false || empty($vehAvails)) {
        Log::warning('LocautoRent: XPath not working, trying direct access');
        // Try to navigate the structure directly
        $body = $xmlObject->xpath('//soap:Body');
        if (!empty($body)) {
          $bodyElement = $body[0];
          // Access children directly
          $responseElement = $bodyElement->children('https://nextrent.locautorent.com')->OTA_VehAvailRateRSResponse;
          if ($responseElement) {
            $resultElement = $responseElement->children('http://www.opentravel.org/OTA/2003/05')->OTA_VehAvailRateRSResult;
            if ($resultElement) {
              $coreElement = $resultElement->VehAvailRSCore;
              if ($coreElement) {
                $vendorAvails = $coreElement->VehVendorAvails;
                if ($vendorAvails) {
                  $vehAvails = $vendorAvails->VehVendorAvail->VehAvails->VehAvail;
                  if ($vehAvails) {
                    // Convert SimpleXMLElement to array if it's a single element
                    $vehAvails = iterator_to_array($vehAvails);
                  }
                }
              }
            }
          }
        }
      }

      Log::info('LocautoRent: Found ' . (is_countable($vehAvails) ? count($vehAvails) : 0) . ' VehAvail elements');

      if (!empty($vehAvails)) {
        foreach ($vehAvails as $vehAvail) {
          // Get the VehAvailCore element (access children with default namespace)
          $vehAvailCore = $vehAvail->VehAvailCore;
          if (!$vehAvailCore)
            continue;

          // Get the Vehicle element
          $vehicleNode = $vehAvailCore->Vehicle;
          if (!$vehicleNode)
            continue;

          // Extract vehicle make and model from VehMakeModel
          $vehMakeModel = $vehicleNode->VehMakeModel;
          $modelYear = $vehMakeModel ? (string) $vehMakeModel['ModelYear'] : 'Unknown Vehicle';
          // ModelYear in Locauto actually contains the vehicle name (e.g., "Toyota Aygo")
          $brand = explode(' ', $modelYear)[0] ?? 'Locauto';
          $model = $modelYear;

          // Get SIPP/ACRISS code
          $sippCode = (string) ($vehicleNode['Code'] ?? '');

          // Get vehicle type info
          $vehType = $vehicleNode->VehType;
          $doorCount = $vehType ? (int) ($vehType['DoorCount'] ?? 4) : 4;

          // Get transmission and passengers
          $transmission = strtolower((string) ($vehicleNode['TransmissionType'] ?? 'manual'));
          $passengers = (int) ($vehicleNode['PassengerQuantity'] ?? 4);
          $luggage = (int) ($vehicleNode['BaggageQuantity'] ?? 2);

          // Get picture URL
          $pictureUrl = (string) ($vehicleNode->PictureURL ?? '');

          // Get pricing from TotalCharge
          $totalCharge = $vehAvailCore->TotalCharge;
          $totalAmount = $totalCharge ? (float) ($totalCharge['RateTotalAmount'] ?? 0) : 0;
          $currency = $totalCharge ? (string) ($totalCharge['CurrencyCode'] ?? 'EUR') : 'EUR';

          // Get VehicleCharge for more details
          $rentalRate = $vehAvailCore->RentalRate;
          if ($rentalRate && $rentalRate->VehicleCharges && $rentalRate->VehicleCharges->VehicleCharge) {
            $vehicleCharge = $rentalRate->VehicleCharges->VehicleCharge;
            if (!$totalAmount) {
              $totalAmount = (float) ($vehicleCharge['Amount'] ?? 0);
              $currency = (string) ($vehicleCharge['CurrencyCode'] ?? 'EUR');
            }
          }

          // Get availability status
          $status = (string) ($vehAvailCore['Status'] ?? 'Available');

          // Use SIPP code as stable ID - this ensures the same vehicle can be found on subsequent requests
          $stableId = $sippCode ?: ('locauto_' . md5($model . $transmission . $passengers));

          $vehicle = [
            'id' => $stableId,
            'source' => 'locauto_rent',
            'brand' => $brand,
            'model' => $model,
            'sipp_code' => $sippCode,
            'transmission' => $transmission === 'automatic' ? 'automatic' : 'manual',
            'fuel' => 'petrol', // Locauto doesn't provide fuel type, default to petrol
            'seating_capacity' => $passengers,
            'doors' => $doorCount,
            'luggage' => $luggage,
            'image' => $pictureUrl,
            'total_amount' => $totalAmount,
            'price_per_day' => $totalAmount, // Will be calculated per day in controller if needed
            'currency' => $currency,
            'availability' => $status === 'Available',
          ];

          // Parse extras/equipment if available
          $pricedEquips = $vehAvailCore->PricedEquips;
          if ($pricedEquips) {
            $extras = [];
            foreach ($pricedEquips->PricedEquip ?? [] as $pricedEquip) {
              $equipment = $pricedEquip->Equipment;
              $charge = $pricedEquip->Charge;
              if ($equipment) {
                $extras[] = [
                  'code' => (string) ($equipment['EquipType'] ?? ''),
                  'description' => (string) ($equipment->Description ?? ''),
                  'amount' => $charge ? (float) ($charge['Amount'] ?? 0) : 0,
                  'currency' => $charge ? (string) ($charge['CurrencyCode'] ?? 'EUR') : 'EUR',
                ];
              }
            }
            $vehicle['extras'] = $extras;
          }

          $vehicles[] = $vehicle;
        }
      }

      Log::info('LocautoRent: Parsed ' . count($vehicles) . ' vehicles successfully');

      return $vehicles;
    } catch (Exception $e) {
      Log::error('LocautoRent: Error parsing vehicle response: ' . $e->getMessage());
      return [];
    }
  }

  /**
   * Get complete Locauto locations based on official documentation
   * Locauto uses predefined XML location codes, not a dynamic location API
   */
  public function parseLocationResponse($xmlResponse = null): array
  {
    Log::info('Getting complete Locauto locations list');

    // Complete Italian locations based on Locauto documentation
    // These are the official Locauto XML location codes
    $predefinedLocations = [
      // Major Airports
      ['code' => 'AHO', 'name' => 'Alghero Airport', 'city' => 'Alghero', 'lat' => 40.6361, 'lng' => 8.1200],
      ['code' => 'BGY', 'name' => 'Bergamo Orio al Serio Airport', 'city' => 'Bergamo', 'lat' => 45.6745, 'lng' => 9.7046],
      ['code' => 'BLQ', 'name' => 'Bologna Airport', 'city' => 'Bologna', 'lat' => 44.5354, 'lng' => 11.2887],
      ['code' => 'CAG', 'name' => 'Cagliari Airport', 'city' => 'Cagliari', 'lat' => 39.2515, 'lng' => 9.0544],
      ['code' => 'CT', 'name' => 'Catania Airport', 'city' => 'Catania', 'lat' => 37.4667, 'lng' => 15.0664],
      ['code' => 'FCO', 'name' => 'Rome Fiumicino Airport', 'city' => 'Rome', 'lat' => 41.8012, 'lng' => 12.2389],
      ['code' => 'FLR', 'name' => 'Florence Airport', 'city' => 'Florence', 'lat' => 43.8100, 'lng' => 11.2056],
      ['code' => 'GOA', 'name' => 'Genoa Airport', 'city' => 'Genoa', 'lat' => 44.4133, 'lng' => 8.8326],
      ['code' => 'LIN', 'name' => 'Milan Linate Airport', 'city' => 'Milan', 'lat' => 45.4465, 'lng' => 9.2774],
      ['code' => 'MXP', 'name' => 'Milan Malpensa Airport T1', 'city' => 'Milan', 'lat' => 45.6326, 'lng' => 8.7261],
      ['code' => 'MXP2', 'name' => 'Milan Malpensa Airport T2', 'city' => 'Milan', 'lat' => 45.6326, 'lng' => 8.7261],
      ['code' => 'NAP', 'name' => 'Naples Airport', 'city' => 'Naples', 'lat' => 40.8860, 'lng' => 14.2908],
      ['code' => 'PA', 'name' => 'Palermo Airport', 'city' => 'Palermo', 'lat' => 38.1607, 'lng' => 13.2919],
      ['code' => 'PI', 'name' => 'Pisa Airport', 'city' => 'Pisa', 'lat' => 43.6839, 'lng' => 10.3966],
      ['code' => 'PSR', 'name' => 'Pescara Airport', 'city' => 'Pescara', 'lat' => 42.4433, 'lng' => 14.2016],
      ['code' => 'SUF', 'name' => 'Lamezia Terme Airport', 'city' => 'Lamezia Terme', 'lat' => 38.8945, 'lng' => 16.2445],
      ['code' => 'TO', 'name' => 'Turin Caselle Airport', 'city' => 'Turin', 'lat' => 45.2009, 'lng' => 7.6495],
      ['code' => 'TS', 'name' => 'Trieste Airport Ronchi dei Legionari', 'city' => 'Trieste', 'lat' => 45.8289, 'lng' => 13.4767],
      ['code' => 'TV', 'name' => 'Treviso Airport', 'city' => 'Treviso', 'lat' => 45.6579, 'lng' => 12.1916],
      ['code' => 'VE', 'name' => 'Venice Marco Polo Airport', 'city' => 'Venice', 'lat' => 45.5053, 'lng' => 12.3519],
      ['code' => 'VCE', 'name' => 'Venice Airport', 'city' => 'Venice', 'lat' => 45.5053, 'lng' => 12.3519],
      ['code' => 'VRA', 'name' => 'Verona Airport', 'city' => 'Verona', 'lat' => 45.3944, 'lng' => 10.8967],
      ['code' => 'AOT', 'name' => 'Aosta Airport', 'city' => 'Aosta', 'lat' => 45.7366, 'lng' => 7.3639],
      ['code' => 'BAAPT', 'name' => 'Bari Airport', 'city' => 'Bari', 'lat' => 41.1395, 'lng' => 16.7753],
      ['code' => 'BDS', 'name' => 'Brindisi Airport', 'city' => 'Brindisi', 'lat' => 40.6542, 'lng' => 17.9440],
      ['code' => 'OLB', 'name' => 'Olbia Airport', 'city' => 'Olbia', 'lat' => 40.8941, 'lng' => 9.4975],
      ['code' => 'RC', 'name' => 'Reggio Calabria Airport', 'city' => 'Reggio Calabria', 'lat' => 38.0666, 'lng' => 15.6459],
      ['code' => 'TPS', 'name' => 'Trapani Airport', 'city' => 'Trapani', 'lat' => 37.9100, 'lng' => 12.4778],

      // Train Stations
      ['code' => 'BOC', 'name' => 'Bologna Centrale Train Station', 'city' => 'Bologna', 'lat' => 44.5043, 'lng' => 11.3417],
      ['code' => 'TBS', 'name' => 'Brescia Central Station', 'city' => 'Brescia', 'lat' => 45.5395, 'lng' => 10.2091],
      ['code' => 'MIT', 'name' => 'Milan Central Station', 'city' => 'Milan', 'lat' => 45.4864, 'lng' => 9.1876],
      ['code' => 'PAD', 'name' => 'Padua Station', 'city' => 'Padua', 'lat' => 45.4064, 'lng' => 11.8768],
      ['code' => 'RMT', 'name' => 'Rome Termini Station', 'city' => 'Rome', 'lat' => 41.9027, 'lng' => 12.4964],
      ['code' => 'RMTIB', 'name' => 'Rome Tiburtina Station', 'city' => 'Rome', 'lat' => 41.9042, 'lng' => 12.5286],
      ['code' => 'VRS', 'name' => 'Verona Station', 'city' => 'Verona', 'lat' => 45.4286, 'lng' => 10.9857],
      ['code' => 'VMS', 'name' => 'Venice Mestre Station', 'city' => 'Venice', 'lat' => 45.4932, 'lng' => 12.2436],
      ['code' => 'BAT', 'name' => 'Bari Train Station', 'city' => 'Bari', 'lat' => 41.1171, 'lng' => 16.8719],

      // Downtown/City Locations
      ['code' => 'ANCD', 'name' => 'Ancona Downtown', 'city' => 'Ancona', 'lat' => 43.6158, 'lng' => 13.5189],
      ['code' => 'ARD', 'name' => 'Arezzo Downtown', 'city' => 'Arezzo', 'lat' => 43.4625, 'lng' => 11.8791],
      ['code' => 'ATD', 'name' => 'Asti Downtown', 'city' => 'Asti', 'lat' => 44.9041, 'lng' => 8.2082],
      ['code' => 'BID', 'name' => 'Biella Downtown', 'city' => 'Biella', 'lat' => 45.5658, 'lng' => 8.0568],
      ['code' => 'CED', 'name' => 'Cuneo Downtown', 'city' => 'Cuneo', 'lat' => 44.3845, 'lng' => 7.5425],
      ['code' => 'CTD', 'name' => 'Caltanissetta Downtown', 'city' => 'Caltanissetta', 'lat' => 37.4895, 'lng' => 14.0574],
      ['code' => 'CZD', 'name' => 'Catanzaro Downtown', 'city' => 'Catanzaro', 'lat' => 38.9075, 'lng' => 16.5886],
      ['code' => 'CVT', 'name' => 'Civitavecchia Downtown', 'city' => 'Civitavecchia', 'lat' => 42.0906, 'lng' => 11.7977],
      ['code' => 'COD', 'name' => 'Codogno Downtown', 'city' => 'Codogno', 'lat' => 45.1583, 'lng' => 9.6965],
      ['code' => 'CON', 'name' => 'Conegliano Downtown', 'city' => 'Conegliano', 'lat' => 45.8844, 'lng' => 12.2972],
      ['code' => 'DCS', 'name' => 'Cosenza Downtown', 'city' => 'Cosenza', 'lat' => 39.3001, 'lng' => 16.2556],
      ['code' => 'CRD', 'name' => 'Cremona Downtown', 'city' => 'Cremona', 'lat' => 45.1343, 'lng' => 10.0224],
      ['code' => 'DFE', 'name' => 'Ferrara Downtown', 'city' => 'Ferrara', 'lat' => 44.8367, 'lng' => 11.6198],
      ['code' => 'EFD', 'name' => 'Empoli Downtown', 'city' => 'Empoli', 'lat' => 43.7208, 'lng' => 10.9456],
      ['code' => 'FID', 'name' => 'Florence Downtown', 'city' => 'Florence', 'lat' => 43.7696, 'lng' => 11.2558],
      ['code' => 'FGD', 'name' => 'Foggia Downtown', 'city' => 'Foggia', 'lat' => 41.4626, 'lng' => 15.5447],
      ['code' => 'FRD', 'name' => 'Fiumicino Downtown', 'city' => 'Fiumicino', 'lat' => 41.7730, 'lng' => 12.2367],
      ['code' => 'DGE', 'name' => 'Genoa Downtown', 'city' => 'Genoa', 'lat' => 44.4056, 'lng' => 8.9463],
      ['code' => 'DIM', 'name' => 'Imperia Downtown', 'city' => 'Imperia', 'lat' => 43.8895, 'lng' => 8.0404],
      ['code' => 'DMIA', 'name' => 'Milan Assago Cassala', 'city' => 'Milan', 'lat' => 45.4175, 'lng' => 9.1660],
      ['code' => 'MICOD', 'name' => 'Milan Corvetto Downtown', 'city' => 'Milan', 'lat' => 45.4456, 'lng' => 9.2147],
      ['code' => 'MND', 'name' => 'Mantua Downtown', 'city' => 'Mantua', 'lat' => 45.1667, 'lng' => 10.7833],
      ['code' => 'DMT', 'name' => 'Modena Downtown', 'city' => 'Modena', 'lat' => 44.6478, 'lng' => 10.9254],
      ['code' => 'MED', 'name' => 'Merano Downtown', 'city' => 'Merano', 'lat' => 46.6729, 'lng' => 11.1589],
      ['code' => 'MZD', 'name' => 'Monza Downtown', 'city' => 'Monza', 'lat' => 45.5845, 'lng' => 9.2744],
      ['code' => 'MOD', 'name' => 'Modena City', 'city' => 'Modena', 'lat' => 44.6478, 'lng' => 10.9254],
      ['code' => 'MBD', 'name' => 'Milan Bergamo Downtown', 'city' => 'Bergamo', 'lat' => 45.6981, 'lng' => 9.6773],
      ['code' => 'TNA', 'name' => 'Naples Downtown', 'city' => 'Naples', 'lat' => 40.8518, 'lng' => 14.2681],
      ['code' => 'NOD', 'name' => 'Novara Downtown', 'city' => 'Novara', 'lat' => 45.4452, 'lng' => 8.6186],
      ['code' => 'TPD', 'name' => 'Padua Downtown', 'city' => 'Padua', 'lat' => 45.4064, 'lng' => 11.8768],
      ['code' => 'PRD', 'name' => 'Parma Downtown', 'city' => 'Parma', 'lat' => 44.8014, 'lng' => 10.3279],
      ['code' => 'PVD', 'name' => 'Pavia Downtown', 'city' => 'Pavia', 'lat' => 45.1883, 'lng' => 9.1572],
      ['code' => 'PGD', 'name' => 'Perugia Downtown', 'city' => 'Perugia', 'lat' => 43.1107, 'lng' => 12.3908],
      ['code' => 'DPOM', 'name' => 'Pomezia Downtown', 'city' => 'Pomezia', 'lat' => 41.6747, 'lng' => 12.4974],
      ['code' => 'RMV', 'name' => 'Rome Via Veneto', 'city' => 'Rome', 'lat' => 41.9076, 'lng' => 12.4919],
      ['code' => 'RME', 'name' => 'Rome EUR', 'city' => 'Rome', 'lat' => 41.8359, 'lng' => 12.4697],
      ['code' => 'DRP', 'name' => 'Rome Prati', 'city' => 'Rome', 'lat' => 41.9061, 'lng' => 12.4470],
      ['code' => 'SSG', 'name' => 'Sesto San Giovanni Downtown', 'city' => 'Milan', 'lat' => 45.5324, 'lng' => 9.2351],
      ['code' => 'DNAF', 'name' => 'Naples Fuorigrotta', 'city' => 'Naples', 'lat' => 40.8397, 'lng' => 14.1708],
    ];

    $locations = [];
    foreach ($predefinedLocations as $loc) {
      $locations[] = [
        'id' => 'locauto_' . $loc['code'],
        'label' => $loc['name'],
        'below_label' => $loc['city'] . ', Italy',
        'location' => $loc['name'],
        'city' => $loc['city'],
        'state' => null,
        'country' => 'Italy',
        'latitude' => $loc['lat'],
        'longitude' => $loc['lng'],
        'source' => 'locauto_rent',
        'matched_field' => 'location',
        'provider_location_id' => $loc['code'],
      ];
    }

    Log::info('Loaded ' . count($locations) . ' predefined Locauto locations');
    return $locations;
  }
}
