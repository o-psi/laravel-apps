<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Offline Package Enabled
    |--------------------------------------------------------------------------
    |
    | Control whether offline functionality is enabled. When disabled, the
    | package will fall back to basic PWA functionality without advanced
    | caching strategies.
    |
    */

    'enabled' => env('OFFLINE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Cache Version
    |--------------------------------------------------------------------------
    |
    | Increment this version to force cache invalidation across all users.
    | Useful for deploying updates that need fresh content.
    |
    */

    'cache_version' => env('OFFLINE_CACHE_VERSION', 1),

    /*
    |--------------------------------------------------------------------------
    | Cache Strategies per Route Pattern
    |--------------------------------------------------------------------------
    |
    | Define caching strategies for different route patterns. Patterns support
    | wildcards (*). First match wins, so order matters.
    |
    | Available strategies:
    | - cache-first: Check cache first, fall back to network
    | - network-first: Try network first, fall back to cache
    | - stale-while-revalidate: Serve cached, fetch fresh in background
    | - network-only: Always fetch from network (no cache)
    | - cache-only: Only serve from cache (offline-only)
    |
    */

    'strategies' => [
        // Example patterns:
        // '/dashboard*' => 'cache-first',
        // '/api/*' => 'network-first',
        // '/static/*' => 'cache-first',
        // '/admin/*' => 'network-only',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Cache Strategy
    |--------------------------------------------------------------------------
    |
    | The default strategy to use when no pattern matches. Recommended:
    | 'network-first' for dynamic apps, 'cache-first' for static content.
    |
    */

    'default_strategy' => 'network-first',

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configure cache behavior and limits.
    |
    */

    'cache' => [
        // Maximum age in seconds (default: 24 hours)
        'max_age' => env('OFFLINE_CACHE_MAX_AGE', 86400),

        // Maximum number of cached items per cache
        'max_items' => env('OFFLINE_CACHE_MAX_ITEMS', 100),

        // Whether to exclude query strings when matching cached requests
        'exclude_query_string' => false,

        // Static asset patterns to always cache (cache-first strategy)
        'assets' => [
            '*.js',
            '*.css',
            '*.woff',
            '*.woff2',
            '*.ttf',
            '*.eot',
            '*.svg',
            '*.png',
            '*.jpg',
            '*.jpeg',
            '*.gif',
            '*.webp',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Background Sync
    |--------------------------------------------------------------------------
    |
    | Configure background synchronization for offline requests.
    |
    */

    'sync' => [
        // Enable background sync
        'enabled' => true,

        // Retry interval in milliseconds
        'retry_interval' => 5000,

        // Maximum retry attempts
        'max_retries' => 3,

        // Tag name for background sync
        'tag' => 'offline-sync',
    ],

    /*
    |--------------------------------------------------------------------------
    | Precache
    |--------------------------------------------------------------------------
    |
    | URLs to precache during service worker installation.
    |
    */

    'precache' => [
        '/offline.html',
        // Add more URLs to precache:
        // '/css/app.css',
        // '/js/app.js',
    ],

    /*
    |--------------------------------------------------------------------------
    | Network Timeout
    |--------------------------------------------------------------------------
    |
    | Network timeout in milliseconds before falling back to cache.
    | Only applies to strategies that check network first.
    |
    */

    'network_timeout' => 3000,

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | Enable detailed console logging in the service worker.
    |
    */

    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Cache Names
    |--------------------------------------------------------------------------
    |
    | Configure cache storage names for different types of content.
    |
    */

    'cache_names' => [
        'pages' => 'offline-pages',
        'assets' => 'offline-assets',
        'api' => 'offline-api',
        'runtime' => 'offline-runtime',
    ],
];
