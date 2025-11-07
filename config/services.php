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

    'proxy' => [
        'http' => env('PROXY_HTTP'),
        'https' => env('PROXY_HTTPS'),
    ],

];
