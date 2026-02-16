<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'greenmotion' => [
        'username' => env('GREENMOTION_USERNAME'),
        'password' => env('GREENMOTION_PASSWORD'),
        'url' => env('GREENMOTION_URL', 'https://gmvrl.fusemetrix.com/bespoke/GMWebService.php'),
    ],

    'usave' => [
        'username' => env('USAVE_USERNAME'),
        'password' => env('USAVE_PASSWORD'),
        'url' => env('USAVE_URL'),
    ],

    'okmobility' => [
        'url' => env('OK_MOBILITY_URL'),
        'customer_code' => env('OK_MOBILITY_CUSTOMER_CODE'),
        'company_code' => env('OK_MOBILITY_COMPANY_CODE'),
    ],

    'wheelsys' => [
        'account_no' => env('WHEELSYS_ACCOUNT_NO'),
        'link_code' => env('WHEELSYS_LINK_CODE'),
        'agent_code' => env('WHEELSYS_AGENT_CODE'),
        'base_url' => env('WHEELSYS_BASE_URL'),
    ],

    'locauto_rent' => [
        'username' => env('LOCAUTO_RENT_USERNAME', 'dpp_vrooem.com'),
        'password' => env('LOCAUTO_RENT_PASSWORD', 'fssgfs99'),
        'test_url' => env('LOCAUTO_RENT_TEST_URL', 'https://nextrent1.locautorent.com/webservices/nextRentOTAService.asmx'),
        'production_url' => env('LOCAUTO_RENT_PRODUCTION_URL', 'https://nextrent.locautorent.com/webservices/nextRentOTAService.asmx'),
        'timeout' => env('LOCAUTO_RENT_TIMEOUT', 30),
    ],

    'renteon' => [
        'username' => env('RENTEON_USERNAME'),
        'password' => env('RENTEON_PASSWORD'),
        'base_url' => env('RENTEON_BASE_URL'),
        'provider_code' => env('RENTEON_PROVIDER_CODE', 'demo'),
        'allowed_providers' => env('RENTEON_ALLOWED_PROVIDERS'),
        'pricelist_codes' => env('RENTEON_PRICELIST_CODES'),
    ],

    'favrica' => [
        'base_url' => env('FAVRICA_BASE_URL', 'http://favricarjson.turevrent.com'),
        'key_hack' => env('FAVRICA_KEY_HACK'),
        'username' => env('FAVRICA_USERNAME'),
        'password' => env('FAVRICA_PASSWORD'),
        'image_base_url' => env('FAVRICA_IMAGE_BASE_URL'),
        'user_agent' => env('FAVRICA_USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'),
        'verify_ssl' => env('FAVRICA_VERIFY_SSL', false),
        'timeout' => env('FAVRICA_TIMEOUT', 30),
        'connect_timeout' => env('FAVRICA_CONNECT_TIMEOUT', 10),
    ],

    'xdrive' => [
        'base_url' => env('XDRIVE_BASE_URL', 'http://xdrivejson.turevsistem.com'),
        'key_hack' => env('XDRIVE_KEY_HACK'),
        'username' => env('XDRIVE_USERNAME'),
        'password' => env('XDRIVE_PASSWORD'),
        'image_base_url' => env('XDRIVE_IMAGE_BASE_URL'),
        'user_agent' => env('XDRIVE_USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'),
        'verify_ssl' => env('XDRIVE_VERIFY_SSL', false),
        'timeout' => env('XDRIVE_TIMEOUT', 30),
        'connect_timeout' => env('XDRIVE_CONNECT_TIMEOUT', 10),
    ],

    'sicily_by_car' => [
        'base_url' => env('SBC_BASE_URL', 'https://booking.sbc.it/dev'),
        'account_code' => env('SBC_ACCOUNT_CODE', 'demo'),
        'api_key' => env('SBC_API_KEY'),
        'timeout' => env('SBC_TIMEOUT', 20),
    ],

    'esim_access' => [
        'api_key' => env('ESIM_ACCESS_API_KEY'),
        'base_url' => env('ESIM_ACCESS_API_URL'),
        'markup_percentage' => env('ESIM_ACCESS_MARKUP_PERCENTAGE', 20),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI'),
    ],

    'proxy' => [
        'http' => env('PROXY_HTTP'),
        'https' => env('PROXY_HTTPS'),
    ],

];
