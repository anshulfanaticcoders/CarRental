<?php

return [
    'enabled' => env('TRABBER_ENABLED', true),

    'api_key' => env('TRABBER_API_KEY'),
    'auth_header' => env('TRABBER_AUTH_HEADER', 'x-api-key'),

    'click_parameter' => env('TRABBER_CLICK_PARAMETER', 'clickid'),
    'attribution_days' => (int) env('TRABBER_ATTRIBUTION_DAYS', 90),
    'commission_rate' => (float) env('TRABBER_COMMISSION_RATE', 0.05),

    'default_currency' => env('TRABBER_DEFAULT_CURRENCY', 'EUR'),
    'default_language' => env('TRABBER_DEFAULT_LANGUAGE', 'en'),
    'default_user_country' => env('TRABBER_DEFAULT_USER_COUNTRY', 'AE'),
    'inventory_scope' => env('TRABBER_INVENTORY_SCOPE', 'mixed'),
    'provider_whitelist' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TRABBER_PROVIDER_WHITELIST', ''))
    ))),
    'location_radius_km' => (float) env('TRABBER_LOCATION_RADIUS_KM', 50),
    'search_limit' => (int) env('TRABBER_SEARCH_LIMIT', 50),
    'offer_ttl_minutes' => (int) env('TRABBER_OFFER_TTL_MINUTES', 60),

    'report_recipient' => env('TRABBER_REPORT_RECIPIENT'),
    'daily_report_filename' => env('TRABBER_DAILY_REPORT_FILENAME', 'vrooem_trabber_daily_report_{date}.csv'),
    'monthly_report_filename' => env('TRABBER_MONTHLY_REPORT_FILENAME', 'vrooem_trabber_monthly_report_{month}.csv'),
];
