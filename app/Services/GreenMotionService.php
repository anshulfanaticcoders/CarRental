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
}
