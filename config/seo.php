<?php

return [
    'defaults' => [
        'title' => env('SEO_DEFAULT_TITLE', env('APP_NAME', 'Vrooem')),
        'description' => env('SEO_DEFAULT_DESCRIPTION', null),
        'image' => env('SEO_DEFAULT_IMAGE', null),
    ],

    'organization' => [
        'name' => env('SEO_ORG_NAME', env('APP_NAME', 'Vrooem')),
        'url' => env('APP_URL'),
        'logo' => env('SEO_ORG_LOGO', null),
        'telephone' => env('SEO_ORG_TELEPHONE', null),
        'email' => env('SEO_ORG_EMAIL', null),
        'address' => [
            'streetAddress' => env('SEO_ORG_ADDRESS_STREET', null),
            'addressLocality' => env('SEO_ORG_ADDRESS_LOCALITY', null),
            'postalCode' => env('SEO_ORG_ADDRESS_POSTAL', null),
            'addressCountry' => env('SEO_ORG_ADDRESS_COUNTRY', null),
        ],
        'sameAs' => array_values(array_filter(array_map('trim', explode(',', env('SEO_ORG_SAMEAS', ''))))),
    ],

    // Allowed route targets for admin SEO management.
    'route_targets' => [
        [
            'key' => 'home',
            'label' => 'Homepage',
            'route_name' => 'welcome',
            'params' => [],
        ],
        [
            'key' => 'faq',
            'label' => 'FAQ',
            'route_name' => 'faq.show',
            'params' => [],
        ],
        [
            'key' => 'contact',
            'label' => 'Contact Us',
            'route_name' => 'contact-us',
            'params' => [],
        ],
        [
            'key' => 'business_register',
            'label' => 'Business Register',
            'route_name' => 'affiliate.business.register',
            'params' => [],
        ],
        [
            'key' => 'blog_listing',
            'label' => 'Blog Listing (Country)',
            'route_name' => 'blog',
            'params' => ['country' => null],
        ],
    ],
];
