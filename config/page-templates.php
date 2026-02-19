<?php

return [
    'default' => [
        'name' => 'Default Page',
        'description' => 'Simple page with title and rich text content',
        'vue_component' => 'Frontend/Templates/DefaultPage',
        'meta_fields' => [],
        'sections' => [
            ['type' => 'content', 'label' => 'Page Content', 'required' => true],
        ],
    ],

    'contact-us' => [
        'name' => 'Contact Us',
        'description' => 'Contact page with company info, form, and map',
        'vue_component' => 'Frontend/Templates/ContactUsPage',
        'meta_fields' => [
            [
                'key' => 'phone_number',
                'label' => 'Phone Number',
                'type' => 'text',
                'translatable' => false,
            ],
            [
                'key' => 'email',
                'label' => 'Email Address',
                'type' => 'email',
                'translatable' => false,
            ],
            [
                'key' => 'address',
                'label' => 'Address',
                'type' => 'textarea',
                'translatable' => true,
            ],
            [
                'key' => 'hero_image_url',
                'label' => 'Hero Image',
                'type' => 'image',
                'translatable' => false,
            ],
            [
                'key' => 'map_link',
                'label' => 'Google Maps Embed URL',
                'type' => 'url',
                'translatable' => false,
            ],
            [
                'key' => 'contact_points',
                'label' => 'Contact Points',
                'type' => 'repeater',
                'translatable' => true,
                'fields' => [
                    ['key' => 'icon', 'label' => 'Icon URL', 'type' => 'image'],
                    ['key' => 'title', 'label' => 'Title', 'type' => 'text'],
                    ['key' => 'detail', 'label' => 'Detail', 'type' => 'text'],
                ],
            ],
        ],
        'sections' => [
            ['type' => 'hero', 'label' => 'Hero Section', 'required' => true],
            ['type' => 'content', 'label' => 'Introduction Text', 'required' => false],
        ],
    ],

    'about-us' => [
        'name' => 'About Us',
        'description' => 'Company story, bio, mission, features, and CTA',
        'vue_component' => 'Frontend/Templates/AboutUsPage',
        'meta_fields' => [
            [
                'key' => 'company_bio',
                'label' => 'Company Bio',
                'type' => 'richtext',
                'translatable' => true,
            ],
            [
                'key' => 'team_image',
                'label' => 'Team Image',
                'type' => 'image',
                'translatable' => false,
            ],
            [
                'key' => 'mission_statement',
                'label' => 'Mission Statement',
                'type' => 'textarea',
                'translatable' => true,
            ],
        ],
        'sections' => [
            ['type' => 'hero', 'label' => 'Hero Section', 'required' => true],
            ['type' => 'content', 'label' => 'Our Story', 'required' => true],
            ['type' => 'features', 'label' => 'Why Choose Us (Features Grid)', 'required' => false],
            ['type' => 'stats', 'label' => 'Stats Section', 'required' => false],
            ['type' => 'split', 'label' => 'Split Content + Image', 'required' => false],
            ['type' => 'cta', 'label' => 'Call to Action', 'required' => false],
        ],
    ],

    'legal' => [
        'name' => 'Legal Page',
        'description' => 'For Privacy Policy, Terms & Conditions, etc.',
        'vue_component' => 'Frontend/Templates/LegalPage',
        'meta_fields' => [
            [
                'key' => 'effective_date',
                'label' => 'Effective Date',
                'type' => 'date',
                'translatable' => false,
            ],
            [
                'key' => 'last_updated',
                'label' => 'Last Updated Date',
                'type' => 'date',
                'translatable' => false,
            ],
        ],
        'sections' => [
            ['type' => 'content', 'label' => 'Legal Content', 'required' => true],
        ],
    ],
];
