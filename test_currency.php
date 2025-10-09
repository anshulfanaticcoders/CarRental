<?php

use Illuminate\Support\Facades\Route;
use Stevebauman\Location\Facades\Location;

// Test route to manually set any country for testing
Route::get('/test-currency/{country}', function ($country) {
    // Map test countries to their real currencies
    $countryMap = [
        'us' => '8.8.8.8',        // USA → USD
        'uk' => '8.8.4.4',        // UK → GBP
        'jp' => '8.8.8.1',        // Japan → JPY
        'de' => '8.8.8.2',        // Germany → EUR
        'au' => '8.8.8.3',        // Australia → AUD
        'in' => '49.43.142.103',  // India → INR (your real IP)
    ];

    $testIp = $countryMap[strtolower($country)] ?? '8.8.8.8';

    // Temporarily enable testing mode with selected IP
    config(['location.testing.enabled' => true]);
    config(['location.testing.ip' => $testIp]);

    try {
        $location = Location::get();

        return response()->json([
            'success' => true,
            'test_country' => strtoupper($country),
            'test_ip' => $testIp,
            'detected_location' => [
                'country_code' => $location->countryCode,
                'country_name' => $location->countryName,
                'city' => $location->cityName,
            ],
            'currency_should_be' => match(strtoupper($location->countryCode)) {
                'US' => 'USD',
                'GB' => 'GBP',
                'JP' => 'JPY',
                'DE', 'FR', 'IT', 'ES', 'NL' => 'EUR',
                'AU' => 'AUD',
                'IN' => 'INR',
                'CA' => 'CAD',
                default => 'USD'
            }
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'test_country' => strtoupper($country),
            'test_ip' => $testIp
        ]);
    }
});

// Test all countries at once
Route::get('/test-all-countries', function () {
    $countries = ['us', 'uk', 'jp', 'de', 'au', 'in'];
    $results = [];

    foreach ($countries as $country) {
        $countryMap = [
            'us' => '8.8.8.8',
            'uk' => '8.8.4.4',
            'jp' => '8.8.8.1',
            'de' => '8.8.8.2',
            'au' => '8.8.8.3',
            'in' => '49.43.142.103'
        ];

        $testIp = $countryMap[$country];
        config(['location.testing.enabled' => true]);
        config(['location.testing.ip' => $testIp]);

        try {
            $location = Location::get();
            $results[$country] = [
                'ip' => $testIp,
                'detected_country' => $location->countryCode,
                'detected_currency' => match(strtoupper($location->countryCode)) {
                    'US' => 'USD', 'GB' => 'GBP', 'JP' => 'JPY',
                    'DE', 'FR', 'IT', 'ES', 'NL' => 'EUR',
                    'AU' => 'AUD', 'IN' => 'INR',
                    'CA' => 'CAD', default => 'USD'
                },
                'success' => true
            ];
        } catch (\Exception $e) {
            $results[$country] = ['error' => $e->getMessage(), 'success' => false];
        }
    }

    return response()->json($results);
});