<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Currency Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for currency detection, conversion, and display features.
    | This includes API keys, cache settings, rate limiting, and fallback options.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency to use when no currency is specified or detected.
    | This should be a valid 3-letter ISO 4217 currency code.
    |
    */
    'default' => env('CURRENCY_DEFAULT', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Base Currency
    |--------------------------------------------------------------------------
    |
    | The base currency used for exchange rate calculations. All exchange
    | rates will be fetched relative to this currency.
    |
    */
    'base_currency' => env('CURRENCY_BASE', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Fallback Currencies
    |--------------------------------------------------------------------------
    |
    | List of currencies to use as fallbacks when detection or conversion
    | fails. These should be commonly accepted currencies.
    |
    */
    'fallback_currencies' => explode(',', env('CURRENCY_FALLBACKS', 'USD,EUR,GBP')),

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for external currency API services including timeouts,
    | retry logic, and rate limiting.
    |
    */
    'request_timeout' => env('CURRENCY_REQUEST_TIMEOUT', 5),
    'max_retries' => env('CURRENCY_MAX_RETRIES', 3),
    'retry_delay' => env('CURRENCY_RETRY_DELAY', 1000), // milliseconds

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Cache settings for exchange rates and currency data. These help
    | reduce API calls and improve performance.
    |
    */
    'cache_ttl' => env('CURRENCY_CACHE_TTL', 3600), // 1 hour in seconds
    'cache_prefix' => env('CURRENCY_CACHE_PREFIX', 'currency'),
    'cache_tag' => env('CURRENCY_CACHE_TAG', 'currency'),

    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker Configuration
    |--------------------------------------------------------------------------
    |
    | Circuit breaker settings to handle API failures gracefully.
    | When failure threshold is reached, the circuit opens temporarily.
    |
    */
    'circuit_breaker_threshold' => env('CURRENCY_CIRCUIT_THRESHOLD', 5),
    'circuit_breaker_timeout' => env('CURRENCY_CIRCUIT_TIMEOUT', 300), // 5 minutes in seconds

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limiting configuration to prevent API abuse and stay within
    | provider limits.
    |
    */
    'rate_limit_per_minute' => env('CURRENCY_RATE_LIMIT_PER_MINUTE', 60),
    'rate_limit_per_hour' => env('CURRENCY_RATE_LIMIT_PER_HOUR', 1000),

    /*
    |--------------------------------------------------------------------------
    | Geolocation Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for IP-based currency detection including which providers
    | to use and their priorities.
    |
    */
    'geolocation' => [
        'providers' => [
            'primary' => [
                'name' => 'ipapi.co',
                'enabled' => env('CURRENCY_GEOLOCATION_PRIMARY_ENABLED', true),
                'timeout' => env('CURRENCY_GEOLOCATION_PRIMARY_TIMEOUT', 5),
                'rate_limit' => env('CURRENCY_GEOLOCATION_PRIMARY_RATE_LIMIT', 1000)
            ],
            'secondary' => [
                'name' => 'ipinfo.io',
                'enabled' => env('CURRENCY_GEOLOCATION_SECONDARY_ENABLED', true),
                'timeout' => env('CURRENCY_GEOLOCATION_SECONDARY_TIMEOUT', 5),
                'rate_limit' => env('CURRENCY_GEOLOCATION_SECONDARY_RATE_LIMIT', 1000)
            ],
            'fallback' => [
                'name' => 'ipify',
                'enabled' => env('CURRENCY_GEOLOCATION_FALLBACK_ENABLED', true),
                'timeout' => env('CURRENCY_GEOLOCATION_FALLBACK_TIMEOUT', 3)
            ]
        ],
        'timeout' => env('CURRENCY_GEOLOCATION_TIMEOUT', 5),
        'max_retries' => env('CURRENCY_GEOLOCATION_MAX_RETRIES', 2)
    ],

    /*
    |--------------------------------------------------------------------------
    | Exchange Rate Providers
    |--------------------------------------------------------------------------
    |
    | Configuration for exchange rate API providers including their
    | priorities, rate limits, and special requirements.
    |
    */
    'exchange_providers' => [
        'primary' => [
            'name' => 'ExchangeRate-API',
            'enabled' => env('CURRENCY_EXCHANGE_PRIMARY_ENABLED', true),
            'timeout' => env('CURRENCY_EXCHANGE_PRIMARY_TIMEOUT', 5),
            'rate_limit' => env('CURRENCY_EXCHANGE_PRIMARY_RATE_LIMIT', 2000),
            'requires_key' => true,
            'base_url' => 'https://v6.exchangerate-api.com/v6',
            'version' => 'v6'
        ],
        'secondary' => [
            'name' => 'Open Exchange Rates',
            'enabled' => env('CURRENCY_EXCHANGE_SECONDARY_ENABLED', false),
            'timeout' => env('CURRENCY_EXCHANGE_SECONDARY_TIMEOUT', 5),
            'rate_limit' => env('CURRENCY_EXCHANGE_SECONDARY_RATE_LIMIT', 1000),
            'requires_key' => true,
            'base_url' => 'https://openexchangerates.org/api',
            'free_tier_limit' => 1000
        ],
        'fallback' => [
            'name' => 'CurrencyLayer',
            'enabled' => env('CURRENCY_EXCHANGE_FALLBACK_ENABLED', false),
            'timeout' => env('CURRENCY_EXCHANGE_FALLBACK_TIMEOUT', 5),
            'rate_limit' => env('CURRENCY_EXCHANGE_FALLBACK_RATE_LIMIT', 1000),
            'requires_key' => true,
            'base_url' => 'https://api.currencylayer.com',
            'free_tier_limit' => 1000
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    |
    | List of currencies that are supported by the application. This
    | helps validate user input and limit the scope of conversions.
    |
    */
    'supported_currencies' => explode(',', env(
        'CURRENCY_SUPPORTED',
        'USD,EUR,GBP,JPY,AUD,CAD,CHF,CNH,HKD,SGD,SEK,KRW,NOK,NZD,INR,MXN,BRL,RUB,ZAR,AED'
    )),

    /*
    |--------------------------------------------------------------------------
    | Precision Configuration
    |--------------------------------------------------------------------------
    |
    | Decimal precision settings for different currencies. Some currencies
    | don't use 2 decimal places (like JPY which uses 0).
    |
    */
    'precision' => [
        'default' => 2,
        'JPY' => 0,
        'KRW' => 0,
        'CLP' => 0,
        'ISK' => 0,
        'BIF' => 0,
        'DJF' => 0,
        'GNF' => 0,
        'KMF' => 0,
        'MGA' => 0,
        'PYG' => 0,
        'RWF' => 0,
        'UGX' => 0,
        'VND' => 0,
        'VUV' => 0,
        'XAF' => 0,
        'XOF' => 0,
        'XPF' => 0
    ],

    /*
    |--------------------------------------------------------------------------
    | User Interface Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for currency-related UI elements including
    | formatting, display options, and user preferences.
    |
    */
    'ui' => [
        'show_currency_selector' => env('CURRENCY_UI_SHOW_SELECTOR', true),
        'show_conversion_info' => env('CURRENCY_UI_SHOW_CONVERSION_INFO', false),
        'auto_detect_currency' => env('CURRENCY_UI_AUTO_DETECT', true),
        'remember_user_preference' => env('CURRENCY_UI_REMEMBER_PREFERENCE', true),
        'refresh_interval' => env('CURRENCY_UI_REFRESH_INTERVAL', 300), // 5 minutes
        'show_loading_states' => env('CURRENCY_UI_SHOW_LOADING', true),
        'show_error_messages' => env('CURRENCY_UI_SHOW_ERRORS', true)
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for currency-related logging including which events
    | to log and the log level to use.
    |
    */
    'logging' => [
        'enabled' => env('CURRENCY_LOGGING_ENABLED', true),
        'level' => env('CURRENCY_LOG_LEVEL', 'warning'),
        'log_conversions' => env('CURRENCY_LOG_CONVERSIONS', false),
        'log_api_calls' => env('CURRENCY_LOG_API_CALLS', true),
        'log_errors' => env('CURRENCY_LOG_ERRORS', true),
        'log_performance' => env('CURRENCY_LOG_PERFORMANCE', false)
    ],

    /*
    |--------------------------------------------------------------------------
    | Background Processing
    |--------------------------------------------------------------------------
    |
    | Configuration for background currency data updates and
    | maintenance tasks.
    |
    */
    'background' => [
        'enabled' => env('CURRENCY_BACKGROUND_ENABLED', true),
        'refresh_interval' => env('CURRENCY_BACKGROUND_REFRESH_INTERVAL', 3600), // 1 hour
        'cleanup_interval' => env('CURRENCY_BACKGROUND_CLEANUP_INTERVAL', 86400), // 24 hours
        'queue_name' => env('CURRENCY_BACKGROUND_QUEUE', 'currency'),
        'max_runtime' => env('CURRENCY_BACKGROUND_MAX_RUNTIME', 300) // 5 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Development & Testing
    |--------------------------------------------------------------------------
    |
    | Development-specific settings for testing currency features
    | without making real API calls.
    |
    */
    'development' => [
        'mock_apis' => env('CURRENCY_DEV_MOCK_APIS', false),
        'mock_rates' => [
            'USD' => 1.0,
            'EUR' => 0.85,
            'GBP' => 0.73,
            'JPY' => 110.0,
            'AUD' => 1.35,
            'CAD' => 1.25
        ],
        'force_currency' => env('CURRENCY_DEV_FORCE_CURRENCY', null),
        'disable_caching' => env('CURRENCY_DEV_DISABLE_CACHE', false)
    ]
];