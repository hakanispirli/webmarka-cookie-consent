<?php

declare(strict_types=1);

return [

    'banner' => [
        'title' => 'Your cookie choices',
        'description' => 'We use strictly necessary cookies to run this site. With your consent we also use analytics and marketing cookies. You can change your choice at any time.',
    ],

    'buttons' => [
        'accept_all' => 'Accept all',
        'reject_all' => 'Reject all',
        'preferences' => 'Manage preferences',
        'save' => 'Save my choices',
        'close' => 'Close',
        'close_reject' => 'Close (reject optional cookies)',
        'cookie_settings' => 'Cookie settings',
    ],

    'links' => [
        'privacy_policy' => 'Privacy notice',
        'cookie_policy' => 'Cookie policy',
    ],

    'preferences' => [
        'title' => 'Cookie preferences',
        'description' => 'Choose which cookie categories you allow. Strictly necessary cookies are required for the site to work and cannot be switched off. Other categories are only enabled with your consent, which you can withdraw at any time.',
        'data_controller' => 'Data controller',
        'always_active' => 'Always active',
        'cookie_details' => 'Cookie details (:count)',
    ],

    'categories' => [
        'necessary' => [
            'title' => 'Strictly necessary',
            'description' => 'Required for core features such as sessions, security and remembering your cookie choice. These cookies do not require consent.',
        ],
        'functional' => [
            'title' => 'Functional',
            'description' => 'Remember choices such as your language or region to give you a more personal experience.',
        ],
        'analytics' => [
            'title' => 'Analytics',
            'description' => 'Help us understand how visitors use the site so we can improve it (e.g. Google Analytics).',
        ],
        'marketing' => [
            'title' => 'Marketing',
            'description' => 'Used to show ads relevant to you and to measure the performance of ad campaigns (e.g. Google Ads).',
        ],
    ],

    'table' => [
        'name' => 'Cookie',
        'category' => 'Category',
        'provider' => 'Provider',
        'purpose' => 'Purpose',
        'duration' => 'Duration',
        'transfers_abroad' => 'Transfer abroad',
        'first_party' => 'First party',
        'no' => 'None',
    ],

    'cookies' => [
        'session' => 'Keeps your session.',
        'xsrf' => 'Protects form submissions against cross-site request forgery (CSRF).',
        'consent' => 'Stores your cookie choices.',
        'ga' => 'Distinguishes visitors to produce site usage statistics.',
        'gcl' => 'Measures ad clicks and conversions.',
    ],

    'durations' => [
        'session' => 'Session',
        'ninety_days' => '90 days',
        'one_year' => '1 year',
        'two_years' => '2 years',
    ],

];
