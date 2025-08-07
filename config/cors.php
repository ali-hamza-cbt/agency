<?php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'web/*', 'login', 'logout'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:3000'], // React app URL
    'allowed_headers' => ['*'],
    'supports_credentials' => true,
    'allowed_origin_patterns' => [],
];
