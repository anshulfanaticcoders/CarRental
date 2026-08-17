<?php

return [
    'enabled' => env('AWIN_ENABLED', false),
    'advertiser_id' => env('AWIN_ADVERTISER_ID', '126167'),
    'api_key' => env('AWIN_API_KEY', ''),
    'test_mode' => env('AWIN_TEST_MODE', true),
    'api_endpoint' => 'https://api.awin.com/s2s/advertiser/',

    // What order value commission is computed on:
    //   collected — amount_paid, the money we actually received (default:
    //               commission can never exceed real revenue)
    //   gross     — total_amount incl. markup, extras and the pay-at-desk
    //               balance we never see (the old, most expensive behavior)
    //   net       — the supplier net (provider_grand_total)
    'commission_base' => env('AWIN_COMMISSION_BASE', 'collected'),
];
