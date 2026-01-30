<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; // Import Log facade

class GreenMotionService
{
    private $baseUrl;
    private $username;
    private $password;

    public function __construct()
    {
        // Default to GreenMotion for backward compatibility
        $this->setProvider('greenmotion');
    }

    public function setProvider(string $provider): self
    {
        $config = config("services.{$provider}");

        if (empty($config['username']) || empty($config['password']) || empty($config['url'])) {
            throw new \Exception("Credentials or URL for provider '{$provider}' are not configured correctly.");
        }

        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->baseUrl = $config['url'];

        return $this;
    }

    public function getVehicles(
        $locationId,
        $startDate,
        $startTime,
        $endDate,
        $endTime,
        $age,
        $options = [] // Added options array for optional parameters
    ) {
        $fuelXml = isset($options['fuel']) ? '<fuel>' . $options['fuel'] . '</fuel>' : '';
        $currencyXml = isset($options['currency']) ? '<currency>' . $options['currency'] . '</currency>' : '';
        $userIdXml = isset($options['userid']) ? '<userid>' . $options['userid'] . '</userid>' : '';
        $usernameXml = isset($options['username']) ? '<username>' . $options['username'] . '</username>' : '';
        $offLocationServiceXml = '';
        if (isset($options['offLocation_service'])) {
            $offLocationServiceXml .= '<offLocation_service>';
            if (isset($options['offLocation_service']['delivery'])) {
                $offLocationServiceXml .= '<delivery km="' . $options['offLocation_service']['delivery']['km'] . '">';
                $offLocationServiceXml .= '<geo lat="' . ($options['offLocation_service']['delivery']['geo']['lat'] ?? '') . '" lng="' . ($options['offLocation_service']['delivery']['geo']['lng'] ?? '') . '" />';
                $offLocationServiceXml .= '</delivery>';
            }
            if (isset($options['offLocation_service']['collection'])) {
                $offLocationServiceXml .= '<collection km="' . $options['offLocation_service']['collection']['km'] . '">';
                $offLocationServiceXml .= '<geo lat="' . ($options['offLocation_service']['collection']['geo']['lat'] ?? '') . '" lng="' . ($options['offLocation_service']['collection']['geo']['lng'] ?? '') . '" />';
                $offLocationServiceXml .= '</collection>';
            }
            $offLocationServiceXml .= '</offLocation_service>';
        }
        $languageXml = isset($options['language']) ? '<language>' . $options['language'] . '</language>' : '';
        $rentalCodeXml = isset($options['rentalCode']) ? '<rentalCode>' . $options['rentalCode'] . '</rentalCode>' : '';
        $fullCreditXml = isset($options['full_credit']) ? '<full_credit>' . $options['full_credit'] . '</full_credit>' : '';
        $promocodeXml = isset($options['promocode']) ? '<promocode>' . $options['promocode'] . '</promocode>' : '';
        $dropoffLocationIdXml = isset($options['dropoff_location_id']) ? '<dropoff_location_id>' . $options['dropoff_location_id'] . '</dropoff_location_id>' : '';


        $xmlRequest = '<?xml version="1.0" encoding="utf-8"?>
            <gm_webservice>
                <header>
                    <username>' . $this->username . '</username>
                    <password>' . $this->password . '</password>
                    <version>1.5</version>
                </header>
                <request type="GetVehicles">
                    <location_id>' . $locationId . '</location_id>
                    ' . $dropoffLocationIdXml . '
                    <start_date>' . $startDate . '</start_date>
                    <start_time>' . $startTime . '</start_time>
                    <end_date>' . $endDate . '</end_date>
                    <end_time>' . $endTime . '</end_time>
                    <age>' . $age . '</age>
                    ' . $fuelXml . '
                    ' . $currencyXml . '
                    ' . $userIdXml . '
                    ' . $usernameXml . '
                    ' . $offLocationServiceXml . '
                    ' . $languageXml . '
                    ' . $rentalCodeXml . '
                    ' . $fullCreditXml . '
                    ' . $promocodeXml . '
                </request>
            </gm_webservice>';

        try {
            Log::info('GreenMotion API Request (GetVehicles): ' . $xmlRequest); // Log request
            $response = Http::withHeaders([
                'Content-Type' => 'application/xml',
            ])
                ->timeout(20)
                ->send('POST', $this->baseUrl, ['body' => $xmlRequest]);

            $response->throw();
            Log::info('GreenMotion API Response (GetVehicles): ' . $response->body()); // Log response

            return $response->body();
        } catch (\Exception $e) {
            Log::error('GreenMotion API Error (GetVehicles): ' . $e->getMessage());
            if (isset($response)) {
                Log::error('GreenMotion API Error Response (GetVehicles): ' . $response->body());
            }
            return null;
        }
    }

    public function getCountryList()
    {
        $xmlRequest = '<?xml version="1.0" encoding="utf-8"?>
            <gm_webservice>
                <header>
                    <username>' . $this->username . '</username>
                    <password>' . $this->password . '</password>
                    <version>1.5</version>
                </header>
                <request type="GetCountryList"></request>
            </gm_webservice>';

        try {
            Log::info('GreenMotion API Request (GetCountryList): ' . $xmlRequest); // Log request
            $response = Http::withHeaders([
                'Content-Type' => 'application/xml',
            ])->send('POST', $this->baseUrl, ['body' => $xmlRequest]);

            $response->throw();
            Log::info('GreenMotion API Response (GetCountryList): ' . $response->body()); // Log response
            Log::debug('Raw XML from GetCountryList: ' . $response->body()); // Add this line for debugging

            return $response->body();
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('GreenMotion API Request Error (GetCountryList): ' . $e->getMessage() . ' Response: ' . $e->response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('GreenMotion API General Error (GetCountryList): ' . $e->getMessage());
            return null;
        }
    }

    public function getTermsAndConditions($countryId, $language = null, $plaintext = null)
    {
        $languageXml = $language ? '<language>' . $language . '</language>' : '';
        $plaintextXml = $plaintext ? '<plaintext>' . $plaintext . '</plaintext>' : '';

        $xmlRequest = '<?xml version="1.0" encoding="utf-8"?>
            <gm_webservice>
                <header>
                    <username>' . $this->username . '</username>
                    <password>' . $this->password . '</password>
                    <version>1.5</version>
                </header>
                <request type="getTermsAndConditions">
                    <country_id>' . $countryId . '</country_id>
                    ' . $languageXml . '
                    ' . $plaintextXml . '
                </request>
            </gm_webservice>';

        try {
            Log::info('GreenMotion API Request (GetTermsAndConditions): ' . $xmlRequest); // Log request
            $response = Http::withHeaders([
                'Content-Type' => 'application/xml',
            ])->send('POST', $this->baseUrl, ['body' => $xmlRequest]);

            $response->throw();
            Log::info('GreenMotion API Response (GetTermsAndConditions): ' . $response->body()); // Log response

            return $response->body();
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('GreenMotion API Request Error (GetTermsAndConditions): ' . $e->getMessage() . ' Response: ' . $e->response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('GreenMotion API General Error (GetTermsAndConditions): ' . $e->getMessage());
            return null;
        }
    }

    public function getRegionList($countryId)
    {
        $xmlRequest = '<?xml version="1.0" encoding="utf-8"?>
            <gm_webservice>
                <header>
                    <username>' . $this->username . '</username>
                    <password>' . $this->password . '</password>
                    <version>1.5</version>
                </header>
                <request type="GetRegionList">
                    <country_id>' . $countryId . '</country_id>
                </request>
            </gm_webservice>';

        try {
            Log::info('GreenMotion API Request (GetRegionList): ' . $xmlRequest); // Log request
            $response = Http::withHeaders([
                'Content-Type' => 'application/xml',
            ])->send('POST', $this->baseUrl, ['body' => $xmlRequest]);

            $response->throw();
            Log::info('GreenMotion API Response (GetRegionList): ' . $response->body()); // Log response

            return $response->body();
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('GreenMotion API Request Error (GetRegionList): ' . $e->getMessage() . ' Response: ' . $e->response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('GreenMotion API General Error (GetRegionList): ' . $e->getMessage());
            return null;
        }
    }

    public function getServiceAreas($countryId, $language = null)
    {
        $languageXml = $language ? '<language>' . $language . '</language>' : '';

        $xmlRequest = '<?xml version="1.0" encoding="utf-8"?>
            <gm_webservice>
                <header>
                    <username>' . $this->username . '</username>
                    <password>' . $this->password . '</password>
                    <version>1.5</version>
                </header>
                <request type="GetServiceAreas">
                    <country_id>' . $countryId . '</country_id>
                    ' . $languageXml . '
                </request>
            </gm_webservice>';

        try {
            Log::info('GreenMotion API Request (GetServiceAreas): ' . $xmlRequest); // Log request
            $response = Http::withHeaders([
                'Content-Type' => 'application/xml',
            ])->send('POST', $this->baseUrl, ['body' => $xmlRequest]);

            $response->throw();
            Log::info('GreenMotion API Response (GetServiceAreas): ' . $response->body()); // Log response
            Log::debug('Raw XML from GetServiceAreas: ' . $response->body()); // Add this line for debugging

            return $response->body();
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('GreenMotion API Request Error (GetServiceAreas): ' . $e->getMessage() . ' Response: ' . $e->response->body());
            Log::debug('Raw XML from GetServiceAreas (error response): ' . ($e->response ? $e->response->body() : 'N/A')); // Log error response body
            return null;
        } catch (\Exception $e) {
            Log::error('GreenMotion API General Error (GetServiceAreas): ' . $e->getMessage());
            return null;
        }
    }

    public function getLocationInfo($locationId) // Renamed from getLocationList
    {
        $xmlRequest = '<?xml version="1.0" encoding="utf-8"?>
            <gm_webservice>
                <header>
                    <username>' . $this->username . '</username>
                    <password>' . $this->password . '</password>
                    <version>1.5</version>
                </header>
                <request type="GetLocationInfo">
                    <location_id>' . $locationId . '</location_id>
                </request>
            </gm_webservice>';

        try {
            Log::info('GreenMotion API Request (GetLocationInfo): ' . $xmlRequest); // Log request
            $response = Http::withHeaders([
                'Content-Type' => 'application/xml',
            ])->send('POST', $this->baseUrl, ['body' => $xmlRequest]);

            $response->throw();
            Log::info('GreenMotion API Response (GetLocationInfo): ' . $response->body()); // Log response

            return $response->body();
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('GreenMotion API Request Error (GetLocationInfo): ' . $e->getMessage() . ' Response: ' . $e->response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('GreenMotion API General Error (GetLocationInfo): ' . $e->getMessage());
            return null;
        }
    }

    public function makeReservation(
        $locationId,
        $startDate,
        $startTime,
        $endDate,
        $endTime,
        $age,
        $customerDetails,
        $vehicleId,
        $vehicleTotal,
        $currency,
        $grandTotal,
        $paymentHandlerRef,
        $quoteId,
        $options = [], // Renamed from $extras to $options for consistency with API docs
        $dropOffLocationId = null,
        $paymentType = 'POA', // Default to POA as per plan
        $rentalCode = null, // Added rentalCode
        $remarks = null // Added remarks
    ) {
        $optionsXml = '';
        foreach ($options as $option) {
            $prePayAttr = isset($option['pre_pay']) && $option['pre_pay'] === 'Yes' ? ' pre_pay="Yes"' : '';
            $optionsXml .= '<option id="' . $option['id'] . '" option_qty="' . $option['option_qty'] . '" option_total="' . $option['option_total'] . '"' . $prePayAttr . ' />';
        }

        $dropOffLocationXml = $dropOffLocationId ? '<dropoff_location_id>' . $dropOffLocationId . '</dropoff_location_id>' : '';
        $rentalCodeXml = $rentalCode ? '<rentalCode>' . $rentalCode . '</rentalCode>' : '';
        $paymentHandlerRefXml = $paymentHandlerRef ? '<paymentHandlerRef>' . $paymentHandlerRef . '</paymentHandlerRef>' : '';
        $paymentTypeXml = $paymentType ? '<payment_type>' . $paymentType . '</payment_type>' : '';
        $quoteIdXml = $quoteId ? '<quoteid>' . $quoteId . '</quoteid>' : '';
        $remarksXml = $remarks ? '<remarks>' . $remarks . '</remarks>' : '';

        $xmlRequest = '<?xml version="1.0" encoding="utf-8"?>
            <gm_webservice>
                <header>
                    <username>' . $this->username . '</username>
                    <password>' . $this->password . '</password>
                    <version>1.5</version>
                </header>
                <request type="MakeReservation">
                    <location_id>' . $locationId . '</location_id>
                    ' . $dropOffLocationXml . '
                    <start_date>' . $startDate . '</start_date>
                    <start_time>' . $startTime . '</start_time>
                    <end_date>' . $endDate . '</end_date>
                    <end_time>' . $endTime . '</end_time>
                    <vehicle_id>' . $vehicleId . '</vehicle_id>
                    ' . $rentalCodeXml . '
                    <vehicle_total>' . $vehicleTotal . '</vehicle_total>
                    <currency>' . $currency . '</currency>
                    <options>' . $optionsXml . '</options>
                    <grand_total>' . $grandTotal . '</grand_total>
                    <cust_info>
                        <firstname>' . ($customerDetails['firstname'] ?? '') . '</firstname>
                        <lastname>' . ($customerDetails['surname'] ?? '') . '</lastname>
                        <age>' . $age . '</age>
                        <telephone>' . ($customerDetails['phone'] ?? '') . '</telephone>
                        <mobile>' . ($customerDetails['mobile'] ?? $customerDetails['phone'] ?? '') . '</mobile>
                        <email>' . ($customerDetails['email'] ?? '') . '</email>
                        <flight_no>' . ($customerDetails['flight_number'] ?? '') . '</flight_no>
                        <address1>' . ($customerDetails['address1'] ?? '') . '</address1>
                        <address2>' . ($customerDetails['address2'] ?? '') . '</address2>
                        <address3>' . ($customerDetails['address3'] ?? '') . '</address3>
                        <city>' . ($customerDetails['town'] ?? '') . '</city>
                        <county>' . ($customerDetails['county'] ?? '') . '</county>
                        <postcode>' . ($customerDetails['postcode'] ?? '') . '</postcode>
                        <country>' . ($customerDetails['country'] ?? '') . '</country>
                        <bplace>' . ($customerDetails['bplace'] ?? '') . '</bplace>
                        <bdate>' . ($customerDetails['bdate'] ?? '') . '</bdate>
                        <idno>' . ($customerDetails['idno'] ?? '') . '</idno>
                        <idplace>' . ($customerDetails['idplace'] ?? '') . '</idplace>
                        <idissue>' . ($customerDetails['idissue'] ?? '') . '</idissue>
                        <idexp>' . ($customerDetails['idexp'] ?? '') . '</idexp>
                        <licno>' . ($customerDetails['driver_licence_number'] ?? '') . '</licno>
                        <licissue>' . ($customerDetails['licissue'] ?? '') . '</licissue>
                        <licplace>' . ($customerDetails['licplace'] ?? '') . '</licplace>
                        <licexp>' . ($customerDetails['licexp'] ?? '') . '</licexp>
                        <idurl>' . ($customerDetails['idurl'] ?? '') . '</idurl>
                        <id_rear_url>' . ($customerDetails['id_rear_url'] ?? '') . '</id_rear_url>
                        <licurl>' . ($customerDetails['licurl'] ?? '') . '</licurl>
                        <lic_rear_url>' . ($customerDetails['lic_rear_url'] ?? '') . '</lic_rear_url>
                        <verification_response>' . ($customerDetails['verification_response'] ?? '') . '</verification_response>
                        <custimage>' . ($customerDetails['custimage'] ?? '') . '</custimage>
                        <dvlacheckcode>' . ($customerDetails['dvlacheckcode'] ?? '') . '</dvlacheckcode>
                    </cust_info>
                    ' . $paymentHandlerRefXml . '
                    ' . $paymentTypeXml . '
                    ' . $quoteIdXml . '
                    ' . $remarksXml . '
                </request>
            </gm_webservice>';

        try {
            Log::info('GreenMotion API Request (MakeReservation): ' . $xmlRequest); // Log request
            $response = Http::withHeaders([
                'Content-Type' => 'application/xml',
            ])->send('POST', $this->baseUrl, ['body' => $xmlRequest]);

            $response->throw();
            Log::info('GreenMotion API Response (MakeReservation): ' . $response->body()); // Log response

            return $response->body();
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('GreenMotion API Request Error (MakeReservation): ' . $e->getMessage() . ' Response: ' . $e->response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('GreenMotion API General Error (MakeReservation): ' . $e->getMessage());
            return null;
        }
    }

    public function cancelReservation($locationId, $bookingRef, $reason = null)
    {
        $reasonText = trim((string) $reason);
        if ($reasonText === '') {
            $reasonText = 'Cancelled by user';
        }

        $xmlRequest = '<?xml version="1.0" encoding="utf-8"?>
            <gm_webservice>
                <header>
                    <username>' . $this->username . '</username>
                    <password>' . $this->password . '</password>
                    <version>1.5</version>
                </header>
                <request type="Cancel Reservation">
                    <location_id>' . $locationId . '</location_id>
                    <booking_ref>' . $bookingRef . '</booking_ref>
                    <cancellationreason>' . htmlspecialchars($reasonText, ENT_XML1) . '</cancellationreason>
                </request>
            </gm_webservice>';

        try {
            Log::info('GreenMotion API Request (Cancel Reservation): ' . $xmlRequest);
            $response = Http::withHeaders([
                'Content-Type' => 'application/xml',
            ])->send('POST', $this->baseUrl, ['body' => $xmlRequest]);

            $response->throw();
            Log::info('GreenMotion API Response (Cancel Reservation): ' . $response->body());

            return $response->body();
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('GreenMotion API Request Error (Cancel Reservation): ' . $e->getMessage() . ' Response: ' . $e->response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('GreenMotion API General Error (Cancel Reservation): ' . $e->getMessage());
            return null;
        }
    }
}
