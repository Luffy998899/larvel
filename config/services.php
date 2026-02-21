<?php

return [
    'pterodactyl' => [
        'url' => env('PTERO_URL'),
        'api_key' => env('PTERO_API_KEY'),
        'node_id' => (int) env('PTERO_NODE_ID', 1),
        'egg_id' => (int) env('PTERO_EGG_ID', 1),
        'location_id' => (int) env('PTERO_LOCATION_ID', 1),
        'nest_id' => (int) env('PTERO_DEFAULT_NEST_ID', 1),
        'docker_image' => env('PTERO_DEFAULT_DOCKER_IMAGE', 'ghcr.io/pterodactyl/yolks:java_17'),
    ],
    'ad_provider' => [
        'webhook_secret' => env('AD_WEBHOOK_SECRET'),
    ],
];
