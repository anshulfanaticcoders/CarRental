<?php

return [
    'awin' => [
        'locale' => env('AWIN_MERCHANT_FEED_LOCALE', 'en'),
        'currency' => env('AWIN_MERCHANT_FEED_CURRENCY', 'EUR'),
        'driver_age' => (int) env('AWIN_MERCHANT_FEED_DRIVER_AGE', 35),
        'pickup_offset_days' => (int) env('AWIN_MERCHANT_FEED_PICKUP_OFFSET_DAYS', 1),
        'rental_days' => (int) env('AWIN_MERCHANT_FEED_RENTAL_DAYS', 1),
        'pickup_time' => env('AWIN_MERCHANT_FEED_PICKUP_TIME', '09:00'),
        'dropoff_time' => env('AWIN_MERCHANT_FEED_DROPOFF_TIME', '09:00'),
        'include_internal' => env('AWIN_MERCHANT_FEED_INCLUDE_INTERNAL', true),
        'include_external' => env('AWIN_MERCHANT_FEED_INCLUDE_EXTERNAL', true),
        'external_location_limit' => (int) env('AWIN_MERCHANT_FEED_EXTERNAL_LOCATION_LIMIT', 50),
        'stale_after_hours' => (int) env('AWIN_MERCHANT_FEED_STALE_AFTER_HOURS', 6),
        'expires_after_hours' => (int) env('AWIN_MERCHANT_FEED_EXPIRES_AFTER_HOURS', 24),
        'output_path' => env('AWIN_MERCHANT_FEED_OUTPUT_PATH', 'feeds/awin/google-merchant.xml'),
        'title' => env('AWIN_MERCHANT_FEED_TITLE', 'Vrooem Vehicle Rental Feed'),
        'description' => env('AWIN_MERCHANT_FEED_DESCRIPTION', 'Bookable Vrooem vehicle rental offers for affiliate partners.'),
        'product_type' => env('AWIN_MERCHANT_FEED_PRODUCT_TYPE', 'Car Rental'),
        'condition' => env('AWIN_MERCHANT_FEED_CONDITION', 'used'),
        'utm' => [
            'utm_source' => 'awin',
            'utm_medium' => 'affiliate',
            'utm_campaign' => 'product-feed',
        ],
    ],
];
