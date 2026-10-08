<?php

return [
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // Only the storefront may call the API from a browser.
    'allowed_origins' => array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', 'https://www.peacockgenuineleather.com'))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
