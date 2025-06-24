<?php

use Itsemon245\Metrics\Facades\Metrics;

if (! function_exists('metrics')) {
    /**
     * Get the metrics instance or record a metric.
     */
    function metrics(?string $name = null, ?float $value = null, array $tags = []): mixed
    {
        if ($name === null) {
            return app('metrics');
        }

        if ($value === null) {
            return Metrics::increment($name, 1, $tags);
        }

        return Metrics::record($name, $value, $tags);
    }
}

if (! function_exists('metrics_time')) {
    /**
     * Time a function execution and record it as a metric.
     */
    function metrics_time(string $name, callable $callback, array $tags = []): mixed
    {
        return Metrics::time($name, $callback, $tags);
    }
}

if (! function_exists('metrics_increment')) {
    /**
     * Increment a counter metric.
     */
    function metrics_increment(string $name, int $value = 1, array $tags = []): void
    {
        Metrics::increment($name, $value, $tags);
    }
}

if (! function_exists('metrics_decrement')) {
    /**
     * Decrement a counter metric.
     */
    function metrics_decrement(string $name, int $value = 1, array $tags = []): void
    {
        Metrics::decrement($name, $value, $tags);
    }
}

if (! function_exists('metrics_flush')) {
    /**
     * Flush cached metrics to database.
     */
    function metrics_flush(): int
    {
        return Metrics::flush();
    }
}

if (! function_exists('metrics_get')) {
    /**
     * Get metrics from database with optional filters.
     */
    function metrics_get(array $filters = []): array
    {
        return Metrics::getMetrics($filters);
    }
}

if (! function_exists('metrics_clean')) {
    /**
     * Clean old metrics from database.
     */
    function metrics_clean(int $daysToKeep = 30): int
    {
        return Metrics::clean($daysToKeep);
    }
}
