<?php

use Itsemon245\Lamet\Facades\Metrics;

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

if (! function_exists('metricsTime')) {
    /**
     * Time a function execution and record it as a metric.
     */
    function metricsTime(string $name, callable $callback, array $tags = []): mixed
    {
        return Metrics::time($name, $callback, $tags);
    }
}

if (! function_exists('metricsIncrement')) {
    /**
     * Increment a counter metric.
     */
    function metricsIncrement(string $name, int $value = 1, array $tags = []): void
    {
        Metrics::increment($name, $value, $tags);
    }
}

if (! function_exists('metricsDecrement')) {
    /**
     * Decrement a counter metric.
     */
    function metricsDecrement(string $name, int $value = 1, array $tags = []): void
    {
        Metrics::decrement($name, $value, $tags);
    }
}

if (! function_exists('metricsDbQuery')) {
    /**
     * Record a database query metric.
     */
    function metricsDbQuery(\Illuminate\Database\Events\QueryExecuted $event, array $additionalTags = [], ?string $name = null): void
    {
        Metrics::dbQuery($event, $additionalTags, $name);
    }
}

if (! function_exists('metricsException')) {
    /**
     * Record an exception metric.
     */
    function metricsException(\Throwable $throwable, array $additionalTags = [], ?string $name = null): void
    {
        Metrics::exception($throwable, $additionalTags, $name);
    }
}

if (! function_exists('metricsFlush')) {
    /**
     * Flush cached metrics to database.
     */
    function metricsFlush(): int
    {
        return Metrics::flush();
    }
}

if (! function_exists('metricsGet')) {
    /**
     * Get metrics from database with optional filters.
     */
    function metricsGet(array $filters = []): array
    {
        return Metrics::getMetrics($filters);
    }
}

if (! function_exists('metricsClean')) {
    /**
     * Clean old metrics from database.
     */
    function metricsClean(int $daysToKeep = 30): int
    {
        return Metrics::clean($daysToKeep);
    }
}
