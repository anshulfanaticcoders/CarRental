<?php

return [
    'enabled' => env('VROOEM_GATEWAY_ENABLED', false),
    'url' => env('VROOEM_GATEWAY_URL', env('VROOEM_GATEWAY_BASE_URL', 'http://localhost:8001')),
    'api_key' => env('VROOEM_GATEWAY_API_KEY', ''),
    'timeout' => env('VROOEM_GATEWAY_TIMEOUT', 60),
    'connect_timeout' => env('VROOEM_GATEWAY_CONNECT_TIMEOUT', 5),
    'internal_api_token' => env('GATEWAY_INTERNAL_TOKEN'),

    // Platform markup applied to partner-API quotes and bookings, in percent.
    // Default 0 = partner bookings pass through at vendor price (no silent
    // price change); setting it records platform_commission + vendor_net on
    // every booking for settlement.
    'partner_markup_percent' => (float) env('PARTNER_API_MARKUP_PERCENT', 0),
];
