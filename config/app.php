<?php

return [
    'name' => env('APP_NAME', 'RevactylHost'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',

    'providers' => Illuminate\Support\ServiceProvider::defaultProviders()->merge(
        require base_path('bootstrap/providers.php')
    )->toArray(),

    'aliases' => Illuminate\Support\Facades\Facade::defaultAliases()->toArray(),
];
