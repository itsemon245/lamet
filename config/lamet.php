<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Lamet Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the Laravel Lamet package.
    | You can customize these settings based on your needs.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Enable Metrics
    |--------------------------------------------------------------------------
    |
    | Set this to false to disable all metrics recording.
    |
    */
    'enabled' => env('LAMET_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Log Metrics
    |--------------------------------------------------------------------------
    |
    | When enabled, metrics will be logged to Laravel's log system.
    | This is useful for debugging and development.
    |
    */
    'log_metrics' => env('LAMET_LOG', false),

    /*
    |--------------------------------------------------------------------------
    | Default Tags
    |--------------------------------------------------------------------------
    |
    | These tags will be automatically added to all metrics.
    |
    */
    'default_tags' => [
        'environment' => env('APP_ENV', 'production'),
        'app_name' => env('APP_NAME', 'laravel'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the metrics cache system.
    |
    */
    'cache' => [
        'store' => env('LAMET_CACHE_STORE', 'redis'),
        'prefix' => env('LAMET_CACHE_PREFIX', 'metrics:'),
        'ttl' => env('LAMET_CACHE_TTL', 3600), // should not be smaller than flush_interval
        'batch_size' => env('LAMET_CACHE_BATCH_SIZE', 1000),
        'flush_interval' => env('LAMET_CACHE_FLUSH_INTERVAL', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Table and Connection
    |--------------------------------------------------------------------------
    |
    | The table and connection to use for storing metrics. If connection is null,
    | metrics will not be stored in the database.
    |
    */
    'table' => env('LAMET_TABLE', 'metrics'),
    'connection' => env('LAMET_DB_CONNECTION', 'pgsql'),
];
