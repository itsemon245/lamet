<?php

namespace Itsemon245\Lamet;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Events\QueryExecuted;
use Itsemon245\Lamet\Traits\HasMetricsCache;
use Itsemon245\Lamet\Traits\HasMetricsDatabase;
use Itsemon245\Lamet\Traits\HasMetricsLogging;

class MetricsManager
{
    use HasMetricsCache, HasMetricsDatabase, HasMetricsLogging;

    /**
     * The application instance.
     */
    protected Application $app;

    /**
     * The lamet configuration.
     */
    protected array $config;

    /**
     * Create a new metrics manager instance.
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->config = $app['config']->get('lamet', []);
    }

    /**
     * Record a metric.
     */
    public function record(string $name, float $value, array $tags = [], ?string $type = null, ?string $unit = null): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        // Check if we should ignore this metric based on current request path
        if ($this->shouldIgnorePath()) {
            return;
        }

        // Add default tags
        $tags = array_merge($this->getDefaultTags(), $tags);
        $type = $type ?? 'counter';
        $unit = $unit ?? null;
        $this->cacheMetric($name, $value, $tags, $type, $unit);
        if ($this->isLoggingEnabled()) {
            $this->logMetric($name, $value, $tags);
        }
    }

    /**
     * Record a database query metric.
     */
    public function dbQuery(QueryExecuted $event, array $additionalTags = [], ?string $name = null): void
    {
        if (! $this->isDbQueryEnabled()) {
            return;
        }

        $dbQueryConfig = $this->config['db_query'] ?? [];
        $metricName = $name ?? ($dbQueryConfig['metric_name'] ?? 'db.query');
        $tags = $additionalTags;

        // Calculate duration in milliseconds
        $duration = $event->time;

        // Get SQL query
        $sql = $event->sql;

        // Get file and line from the event's connection or fallback to debug_backtrace
        $file = '';
        $line = 0;

        // Try to get from backtrace since QueryExecuted event doesn't provide file/line directly
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        foreach ($backtrace as $trace) {
            if (isset($trace['file']) &&
                ! str_contains($trace['file'], 'vendor/laravel') &&
                ! str_contains($trace['file'], 'MetricsManager.php')) {
                $file = $trace['file'];
                $line = $trace['line'];
                break;
            }
        }

        // Add configured tags
        foreach ($dbQueryConfig['tags'] ?? [] as $tag) {
            switch ($tag) {
                case 'sql':
                    $tags['sql'] = $this->normalizeSql($sql);
                    break;
                case 'duration':
                    $tags['duration'] = round($duration, 2);
                    break;
                case 'file':
                    $tags['file'] = $file;
                    break;
                case 'line':
                    $tags['line'] = $line;
                    break;
            }
        }

        // Record the main query metric
        $this->record($metricName, $duration, $tags, 'timer', 'ms');

        // Record separate metric for slow queries if enabled
        if (($dbQueryConfig['separate_metric_for_slow_query'] ?? false) &&
            $duration >= ($dbQueryConfig['slow_query_threshold'] ?? 1500)) {
            $this->record($metricName.'.slow', $duration, $tags, 'timer', 'ms');
        }
    }

    /**
     * Record an exception metric.
     */
    public function exception(\Throwable $throwable, array $additionalTags = [], ?string $name = null): void
    {
        if (! $this->isExceptionEnabled()) {
            return;
        }

        // Check if we should ignore this exception
        if ($this->shouldIgnoreException($throwable)) {
            return;
        }

        $exceptionConfig = $this->config['exception'] ?? [];
        $metricName = $name ?? ($exceptionConfig['metric_name'] ?? 'exception.occurrence');
        $tags = $additionalTags;

        // Add configured tags
        foreach ($exceptionConfig['tags'] ?? [] as $tag) {
            switch ($tag) {
                case 'exception_class':
                    $tags['exception_class'] = get_class($throwable);
                    break;
                case 'message':
                    $tags['message'] = $throwable->getMessage();
                    break;
                case 'file':
                    $tags['file'] = $throwable->getFile();
                    break;
                case 'line':
                    $tags['line'] = $throwable->getLine();
                    break;
                case 'code':
                    $tags['code'] = $throwable->getCode();
                    break;
                case 'trace':
                    if (method_exists($throwable, 'getTraceAsString')) {
                        $trace = $throwable->getTraceAsString();
                        if (strlen($trace) > 500) {
                            $trace = substr($trace, 0, 497).'...';
                        }
                        $tags['trace'] = $trace;
                    }
                    break;
            }
        }

        // Record the exception metric
        $this->record($metricName, 1, $tags, 'exception');
    }

    /**
     * Increment a counter.
     */
    public function increment(string $name, int $value = 1, array $tags = [], ?string $type = null, ?string $unit = null): void
    {
        $this->record($name, $value, $tags, $type, $unit);
    }

    /**
     * Decrement a counter.
     */
    public function decrement(string $name, int $value = 1, array $tags = [], ?string $type = null, ?string $unit = null): void
    {
        $this->record($name, -$value, $tags, $type, $unit);
    }

    /**
     * Time a function execution.
     */
    public function time(string $name, callable $callback, array $tags = [], ?string $type = null, ?string $unit = 'ms'): mixed
    {
        $start = microtime(true);
        try {
            $result = $callback();
            $this->record($name, (microtime(true) - $start) * 1000, $tags, $type ?? 'timer', $unit);

            return $result;
        } catch (\Exception $e) {
            // Check if we should ignore this exception
            if ($this->shouldIgnoreException($e)) {
                throw $e;
            }

            $this->record($name, (microtime(true) - $start) * 1000, array_merge($tags, ['error' => true]), $type ?? 'timer', $unit);
            throw $e;
        }
    }

    /**
     * Flush cached metrics to database.
     */
    public function flush(): int
    {
        if (! $this->isEnabled()) {
            return 0;
        }
        $metrics = $this->getCachedMetrics();
        if (empty($metrics)) {
            return 0;
        }
        $this->storeMetricsInDatabase($metrics);
        if ($this->isLoggingEnabled()) {
            $this->logMetricsBatch($metrics);
        }
        $this->clearCachedMetrics();

        return count($metrics);
    }

    /**
     * Get metrics from database with optional filters.
     */
    public function getMetrics(array $filters = []): array
    {
        return $this->getMetricsFromDatabase($filters);
    }

    /**
     * Clean old metrics from database.
     */
    public function clean(int $daysToKeep = 30): int
    {
        return $this->cleanOldMetrics($daysToKeep);
    }

    /**
     * Check if the current request path should be ignored.
     */
    protected function shouldIgnorePath(): bool
    {
        if (! $this->app->bound('request')) {
            return false;
        }

        $request = $this->app['request'];
        $currentPath = $request->path();
        $ignorePaths = $this->config['ignore']['paths'] ?? [];

        foreach ($ignorePaths as $ignorePath) {
            if ($this->pathMatches($currentPath, $ignorePath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an exception should be ignored.
     */
    protected function shouldIgnoreException(\Throwable $throwable): bool
    {
        $ignoreExceptions = $this->config['ignore']['exceptions'] ?? [];
        $exceptionClass = get_class($throwable);

        foreach ($ignoreExceptions as $ignoreException) {
            if ($this->exceptionMatches($exceptionClass, $ignoreException)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a path matches an ignore pattern.
     */
    protected function pathMatches(string $path, string $pattern): bool
    {
        // Handle wildcard patterns like /foo/*
        if (str_ends_with($pattern, '/*')) {
            $basePattern = rtrim($pattern, '/*');

            return str_starts_with($path, $basePattern);
        }

        // Exact match
        return $path === $pattern;
    }

    /**
     * Check if an exception class matches an ignore pattern.
     */
    protected function exceptionMatches(string $exceptionClass, string $pattern): bool
    {
        // Handle wildcard patterns like Foo\*
        if (str_ends_with($pattern, '\*')) {
            $basePattern = rtrim($pattern, '\*');

            return str_starts_with($exceptionClass, $basePattern);
        }

        // Exact match
        return $exceptionClass === $pattern;
    }

    /**
     * Check if database query monitoring is enabled.
     */
    protected function isDbQueryEnabled(): bool
    {
        return $this->isEnabled() && ($this->config['db_query']['enabled'] ?? true);
    }

    /**
     * Normalize SQL query for consistent tagging.
     */
    protected function normalizeSql(string $sql): string
    {
        // Remove extra whitespace and newlines
        $sql = preg_replace('/\s+/', ' ', trim($sql));

        // Limit length to prevent overly long tags
        if (strlen($sql) > 200) {
            $sql = substr($sql, 0, 197).'...';
        }

        return $sql;
    }

    /**
     * Get default tags.
     */
    protected function getDefaultTags(): array
    {
        $tags = $this->config['default_tags'] ?? [];
        $userFields = $tags['user'] ?? [];
        $user = auth()->user();
        if ($user) {
            foreach ($userFields as $field) {
                $tags[$field] = $user->{$field} ?? null;
            }
        }

        return $tags;
    }

    /**
     * Get the configuration.
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Check if metrics are enabled.
     */
    protected function isEnabled(): bool
    {
        return $this->config['enabled'] ?? true;
    }

    /**
     * Check if exception monitoring is enabled.
     */
    protected function isExceptionEnabled(): bool
    {
        return $this->isEnabled() && ($this->config['exception']['enabled'] ?? true);
    }
}
