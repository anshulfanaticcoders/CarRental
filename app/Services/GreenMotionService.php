<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GreenMotionService
{
    private $baseUrl = 'https://gmvrl.fusemetrix.com/bespoke/GMWebService.php';
    private $username = 'GmVEM@2025!';
    private $password = 'GmVEM@2025!';

    public function getVehicles($locationId, $startDate, $startTime, $endDate, $endTime, $rentalCode, $age)
    {
        $xmlRequest = '<?xml version="1.0" encoding="utf-8"?>
            <gm_webservice>
                <header>
                    <username>' . $this->username . '</username>
                    <password>' . $this->password . '</password>
                    <version>1.5</version>
                </header>
                <request type="GetVehicles">
                    <location_id>' . $locationId . '</location_id>
                    <start_date>' . $startDate . '</start_date>
                    <start_time>' . $startTime . '</start_time>
                    <end_date>' . $endDate . '</end_date>
                    <end_time>' . $endTime . '</end_time>
                    <rentalCode>' . $rentalCode . '</rentalCode>
                    <age>' . $age . '</age>
                </request>
            </gm_webservice>';

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/xml',
            ])->send('POST', $this->baseUrl, ['body' => $xmlRequest]);

            $response->throw();

            return $response->body();
        } catch (\Exception $e) {
            // Log the error
            \Log::error('GreenMotion API Error: ' . $e->getMessage());

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
            $response = Http::withHeaders([
                'Content-Type' => 'application/xml',
            ])->send('POST', $this->baseUrl, ['body' => $xmlRequest]);

            $response->throw();

            return $response->body();
        } catch (\Illuminate\Http\Client\RequestException $e) {
            \Log::error('GreenMotion API Request Error (GetCountryList): ' . $e->getMessage() . ' Response: ' . $e->response->body());
            return null;
        } catch (\Exception $e) {
            \Log::error('GreenMotion API General Error (GetCountryList): ' . $e->getMessage());
            return null;
        }
    }

    public function getLocationList($countryId)
    {
        $xmlRequest = '<?xml version="1.0" encoding="utf-8"?>
            <gm_webservice>
                <header>
                    <username>' . $this->username . '</username>
                    <password>' . $this->password . '</password>
                    <version>1.5</version>
                </header>
                <request type="GetLocationList">
                    <country_id>' . $countryId . '</country_id>
                </request>
            </gm_webservice>';

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/xml',
            ])->send('POST', $this->baseUrl, ['body' => $xmlRequest]);

            $response->throw();

            return $response->body();
        } catch (\Illuminate\Http\Client\RequestException $e) {
            \Log::error('GreenMotion API Request Error (GetLocationList): ' . $e->getMessage() . ' Response: ' . $e->response->body());
            return null;
        } catch (\Exception $e) {
            \Log::error('GreenMotion API General Error (GetLocationList): ' . $e->getMessage());
            return null;
        }
    }

    public function makeReservation($locationId, $startDate, $startTime, $endDate, $endTime, $rentalCode, $age, $customerDetails, $extras = [], $dropOffLocationId = null)
    {
        $optionsXml = '';
        foreach ($extras as $extra) {
            $optionsXml .= '<option id="' . $extra['id'] . '" option_qty="' . $extra['option_qty'] . '" option_total="' . $extra['option_total'] . '" pre_pay="' . ($extra['pre_pay'] ?? 'no') . '" />';
        }

        $dropOffLocationXml = $dropOffLocationId ? '<dropoff_location_id>' . $dropOffLocationId . '</dropoff_location_id>' : '';

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
                    <rentalCode>' . $rentalCode . '</rentalCode>
                    <age>' . $age . '</age>
                    <customer>
                        <title>' . ($customerDetails['title'] ?? '') . '</title>
                        <firstname>' . ($customerDetails['firstname'] ?? '') . '</firstname>
                        <surname>' . ($customerDetails['surname'] ?? '') . '</surname>
                        <email>' . ($customerDetails['email'] ?? '') . '</email>
                        <phone>' . ($customerDetails['phone'] ?? '') . '</phone>
                        <address1>' . ($customerDetails['address1'] ?? '') . '</address1>
                        <address2>' . ($customerDetails['address2'] ?? '') . '</address2>
                        <address3>' . ($customerDetails['address3'] ?? '') . '</address3>
                        <town>' . ($customerDetails['town'] ?? '') . '</town>
                        <postcode>' . ($customerDetails['postcode'] ?? '') . '</postcode>
                        <country>' . ($customerDetails['country'] ?? '') . '</country>
                        <driver_licence_number>' . ($customerDetails['driver_licence_number'] ?? '') . '</driver_licence_number>
                        <flight_number>' . ($customerDetails['flight_number'] ?? '') . '</flight_number>
                        <comments>Test Booking - ' . ($customerDetails['comments'] ?? '') . '</comments>
                    </customer>
                    <options>' . $optionsXml . '</options>
                </request>
            </gm_webservice>';

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/xml',
            ])->send('POST', $this->baseUrl, ['body' => $xmlRequest]);

            $response->throw();

            return $response->body();
        } catch (\Illuminate\Http\Client\RequestException $e) {
            \Log::error('GreenMotion API Request Error (MakeReservation): ' . $e->getMessage() . ' Response: ' . $e->response->body());
            return null;
        } catch (\Exception $e) {
            \Log::error('GreenMotion API General Error (MakeReservation): ' . $e->getMessage());
            return null;
        }
    }
}
