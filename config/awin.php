<?php

return [
    'enabled' => env('AWIN_ENABLED', false),
    'advertiser_id' => env('AWIN_ADVERTISER_ID', '126167'),
    'api_key' => env('AWIN_API_KEY', ''),
    'test_mode' => env('AWIN_TEST_MODE', true),
    'api_endpoint' => 'https://api.awin.com/s2s/advertiser/',
];
