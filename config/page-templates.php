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
        'description' => 'Cinematic company story with trust, rental journey, coverage, promises, and CTA',
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
            [
                'type' => 'hero',
                'label' => 'Cinematic Hero',
                'required' => true,
                'show_content' => true,
                'fields' => [
                    ['key' => 'badge', 'label' => 'Badge Text', 'type' => 'text'],
                    ['key' => 'image_url', 'label' => 'Hero Background Image URL', 'type' => 'image'],
                    ['key' => 'image_alt', 'label' => 'Hero Background Alt Text', 'type' => 'text'],
                    ['key' => 'primary_button_text', 'label' => 'Primary Button Text', 'type' => 'text'],
                    ['key' => 'primary_button_url', 'label' => 'Primary Button URL', 'type' => 'url'],
                    ['key' => 'secondary_button_text', 'label' => 'Secondary Button Text', 'type' => 'text'],
                    ['key' => 'secondary_button_url', 'label' => 'Secondary Button URL', 'type' => 'url'],
                    ['key' => 'panel_image_url', 'label' => 'Main Floating Panel Image URL', 'type' => 'image'],
                    ['key' => 'panel_image_alt', 'label' => 'Main Floating Panel Alt Text', 'type' => 'text'],
                    ['key' => 'panel_title', 'label' => 'Floating Panel Title', 'type' => 'text'],
                    ['key' => 'panel_text', 'label' => 'Floating Panel Text', 'type' => 'text'],
                    ['key' => 'side_image_url', 'label' => 'Small Floating Panel Image URL', 'type' => 'image'],
                    ['key' => 'side_image_alt', 'label' => 'Small Floating Panel Alt Text', 'type' => 'text'],
                ],
            ],
            [
                'type' => 'stats',
                'label' => 'Platform Stats',
                'required' => false,
                'fields' => [
                    [
                        'key' => 'items',
                        'label' => 'Stat Items',
                        'type' => 'repeater',
                        'fields' => [
                            ['key' => 'number', 'label' => 'Number (e.g. 800)', 'type' => 'text'],
                            ['key' => 'suffix', 'label' => 'Suffix (e.g. +, /7, K+)', 'type' => 'text'],
                            ['key' => 'label', 'label' => 'Label', 'type' => 'text'],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'content',
                'label' => 'Mission and Trust',
                'required' => true,
                'show_content' => true,
                'fields' => [
                    ['key' => 'kicker', 'label' => 'Small Label', 'type' => 'text'],
                    [
                        'key' => 'proof_items',
                        'label' => 'Trust Chips',
                        'type' => 'repeater',
                        'fields' => [
                            ['key' => 'icon', 'label' => 'Lucide Icon Name', 'type' => 'text'],
                            ['key' => 'title', 'label' => 'Title', 'type' => 'text'],
                            ['key' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                        ],
                    ],
                    [
                        'key' => 'mission_lines',
                        'label' => 'Mission Lines',
                        'type' => 'repeater',
                        'fields' => [
                            ['key' => 'label', 'label' => 'Label', 'type' => 'text'],
                            ['key' => 'title', 'label' => 'Title', 'type' => 'text'],
                            ['key' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'split',
                'label' => 'Rental Journey',
                'required' => false,
                'show_content' => true,
                'fields' => [
                    ['key' => 'kicker', 'label' => 'Small Label', 'type' => 'text'],
                    ['key' => 'image_url', 'label' => 'Journey Image URL', 'type' => 'image'],
                    ['key' => 'image_alt', 'label' => 'Journey Image Alt Text', 'type' => 'text'],
                    ['key' => 'route_note_title', 'label' => 'Route Note Title', 'type' => 'text'],
                    ['key' => 'route_note_text', 'label' => 'Route Note Text', 'type' => 'textarea'],
                    [
                        'key' => 'items',
                        'label' => 'Journey Steps',
                        'type' => 'repeater',
                        'fields' => [
                            ['key' => 'title', 'label' => 'Title', 'type' => 'text'],
                            ['key' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'ribbon',
                'label' => 'Brand Statement',
                'required' => false,
                'show_content' => true,
                'fields' => [
                    ['key' => 'background_image_url', 'label' => 'Background Image URL', 'type' => 'image'],
                    ['key' => 'background_image_alt', 'label' => 'Background Image Alt Text', 'type' => 'text'],
                ],
            ],
            [
                'type' => 'features',
                'label' => 'Customer Promises',
                'required' => false,
                'show_content' => true,
                'fields' => [
                    ['key' => 'kicker', 'label' => 'Small Label', 'type' => 'text'],
                    [
                        'key' => 'items',
                        'label' => 'Promise Cards',
                        'type' => 'repeater',
                        'fields' => [
                            ['key' => 'icon', 'label' => 'Lucide Icon Name', 'type' => 'text'],
                            ['key' => 'kicker', 'label' => 'Small Label', 'type' => 'text'],
                            ['key' => 'title', 'label' => 'Title', 'type' => 'text'],
                            ['key' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'coverage',
                'label' => 'Coverage Gallery',
                'required' => false,
                'show_content' => true,
                'fields' => [
                    ['key' => 'kicker', 'label' => 'Small Label', 'type' => 'text'],
                    [
                        'key' => 'items',
                        'label' => 'Coverage Cards',
                        'type' => 'repeater',
                        'fields' => [
                            ['key' => 'image_url', 'label' => 'Image URL', 'type' => 'image'],
                            ['key' => 'image_alt', 'label' => 'Image Alt Text', 'type' => 'text'],
                            ['key' => 'title', 'label' => 'Title', 'type' => 'text'],
                            ['key' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'cta',
                'label' => 'Call to Action',
                'required' => false,
                'show_content' => true,
                'fields' => [
                    ['key' => 'kicker', 'label' => 'Small Label', 'type' => 'text'],
                    ['key' => 'button_text', 'label' => 'Button Text', 'type' => 'text'],
                    ['key' => 'button_url', 'label' => 'Button URL', 'type' => 'url'],
                ],
            ],
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
