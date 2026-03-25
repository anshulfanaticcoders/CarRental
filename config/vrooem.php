<?php

return [
    'enabled' => env('VROOEM_GATEWAY_ENABLED', false),
    'url' => env('VROOEM_GATEWAY_URL', 'http://localhost:8001'),
    'api_key' => env('VROOEM_GATEWAY_API_KEY', ''),
    'timeout' => env('VROOEM_GATEWAY_TIMEOUT', 60),
    'connect_timeout' => env('VROOEM_GATEWAY_CONNECT_TIMEOUT', 5),
];
