<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Partner Case
    |--------------------------------------------------------------------------
    */
    'case_id' => env('SKYSCANNER_CASE_ID', 'PSM-46100'),

    /*
    |--------------------------------------------------------------------------
    | Skyscanner Integration Toggle
    |--------------------------------------------------------------------------
    |
    | The isolated Skyscanner integration stays disabled until the contract,
    | routing, and tracking pieces are fully implemented and verified.
    |
    */
    'enabled' => env('SKYSCANNER_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Inventory Scope
    |--------------------------------------------------------------------------
    |
    | Initial working assumption for this branch. This keeps the first rollout
    | narrow and avoids disturbing existing mixed-provider flows.
    |
    */
    'inventory_scope' => env('SKYSCANNER_INVENTORY_SCOPE', 'internal'),

    'provider_whitelist' => array_values(array_filter(array_map(
        static fn ($value) => strtolower(trim((string) $value)),
        explode(',', (string) env('SKYSCANNER_PROVIDER_WHITELIST', ''))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Quote Lifecycle
    |--------------------------------------------------------------------------
    */
    'quote_ttl_minutes' => env('SKYSCANNER_QUOTE_TTL_MINUTES', 30),
    'expired_quote_retention_minutes' => env('SKYSCANNER_EXPIRED_QUOTE_RETENTION_MINUTES', 1440),
    'free_esim_included' => env('SKYSCANNER_FREE_ESIM_INCLUDED', true),

    /*
    |--------------------------------------------------------------------------
    | Security Defaults
    |--------------------------------------------------------------------------
    */
    'api_key' => env('SKYSCANNER_API_KEY', ''),
    'signing_secret' => env('SKYSCANNER_SIGNING_SECRET', ''),
    'allowlisted_ips' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('SKYSCANNER_ALLOWLISTED_IPS', ''))
    ))),
    'testing_access' => [
        'base_path' => env('SKYSCANNER_TEST_BASE_PATH', '/api/skyscanner'),
        'redirect_path' => env('SKYSCANNER_REDIRECT_PATH', '/skyscanner/redirect'),
        'ip_allowlist_required' => env('SKYSCANNER_IP_ALLOWLIST_REQUIRED', true),
        'auth_header' => env('SKYSCANNER_AUTH_HEADER', 'x-api-key'),
        'auth_scheme' => env('SKYSCANNER_AUTH_SCHEME', 'header'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracking And Launch Requirements
    |--------------------------------------------------------------------------
    */
    'dv_required_before_go_live' => env('SKYSCANNER_DV_REQUIRED', true),
    'dv_method' => env('SKYSCANNER_DV_METHOD'),
    'keyword_tracking_enabled' => env('SKYSCANNER_KEYWORD_TRACKING_ENABLED', false),
];
