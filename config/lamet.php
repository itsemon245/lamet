<?php

use Illuminate\Validation\ValidationException;

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
        'app_url' => env('APP_URL', 'http://localhost'),
        'debug_mode' => env('APP_DEBUG', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Query Monitoring
    |--------------------------------------------------------------------------
    |
    | Configuration for monitoring database queries.
    |
    */
    'db_query' => [
        'enabled' => env('LAMET_DB_QUERY_ENABLED', true),
        'metric_name' => 'db.query',
        'tags' => ['connection', 'sql', 'duration', 'file', 'line'],
        'separate_metric_for_slow_query' => env('LAMET_SLOW_QUERY_SEPARATE_METRIC', true),
        'slow_query_threshold' => env('LAMET_SLOW_QUERY_THRESHOLD', 1500), // in ms
    ],
    /*
    |--------------------------------------------------------------------------
    | Exceptions Monitoring
    |--------------------------------------------------------------------------
    |
    | Configuration for monitoring exceptions.
    |
    */
    'exception' => [
        'enabled' => env('LAMET_EXCEPTION_ENABLED', false),// auto query listener is less accurate please follow the docs to get a more accurate exception metrics
        'metric_name' => 'exception.occurrence',
        'tags' => ['exception_class', 'message', 'file', 'line', 'code', 'trace'],
        'trace_lines' => 15, // number of lines to take from the trace
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignore Configuration
    |--------------------------------------------------------------------------
    |
    | Paths and exceptions to ignore when recording metrics.
    |
    */
    'ignore' => [
        /**
         * - /foo/*: ignore all paths that start with foo/
         * - /foo/bar: ignore the exact path /foo/bar
         */
        'paths' => [
            '/health',
            '/metrics',
            '/prometheus',
            '/_ignition/*',
            '/debugbar/*',
            '/clockwork/*',
            '/__clockwork/*',
            '/__debugbar/*',
            '/horizon/*',
            '/telescope/*',
            '/pulse/*',
        ],
        /**
         * - Foo\*: ignore all exceptions under the Foo namespace
         * - Foo\Bar::class: ignore the exact exception class Foo\Bar
         */
        'exceptions' => [
            ValidationException::class,
            'SomeNamespace\*',
        ],

        'db_query' =>[
            /**
             * Ignore any queries that are made to the following tables.
             * NOTE: this will ignore any queries related to that table i.e(JOINs, SELECTs, UPDATEs, DELETEs, etc)
             */
            'tables' => [
                'migrations',
                'jobs',
                'failed_jobs',
                'jobs_batches',
                'jobs_batches_failed',
                'jobs_batches_failed_jobs',
            ],
            /**
             * Ignore any queries that match the following SQL patterns.
             * - Only regex patterns are supported.
             */
            'sql_patterns' => [
                '/^SELECT \* FROM information_schema\.tables$/',
            ]
        ]
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
        'ttl' => env('LAMET_CACHE_TTL', 3600), // make sure it is greater then lamet:flush comand interval in the scheduler
        'batch_size' => env('LAMET_CACHE_BATCH_SIZE', 1000),
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
    'connection' => 'lamet',
];
