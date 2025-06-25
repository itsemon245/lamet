<?php

namespace Itsemon245\Lamet;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Itsemon245\Lamet\Exceptions\LametException;
use Itsemon245\Lamet\Traits\HasMetricsCache;
use Itsemon245\Lamet\Traits\HasMetricsDatabase;
use Itsemon245\Lamet\Traits\HasMetricsLogging;
use Throwable;

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
        $this->cacheStore = Cache::store($this->getCacheStore());
    }

    /**
     * Record a metric.
     */
    public function record(string $name, float $value, array $tags = [], ?string $type = null, ?string $unit = null): mixed
    {
        try {
            $this->logger('Record method called', [
                'name' => $name,
                'value' => $value,
                'tags' => $tags,
                'type' => $type,
                'unit' => $unit,
            ]);

            if (! $this->isEnabled()) {
                $this->logger('Metrics disabled, returning null');

                return null;
            }

            $this->logger('Metrics enabled, proceeding');

            // Check if we should ignore this metric based on current request path
            if ($this->shouldIgnorePath()) {
                $this->logger('Path should be ignored, returning null');

                return null;
            }

            $this->logger('Path not ignored, proceeding');

            // Add default tags
            $tags = array_merge($this->getDefaultTags(), $tags);
            $this->logger('Default tags merged', ['final_tags' => $tags]);

            $type = $type ?? 'counter';
            $unit = $unit ?? null;

            $this->logger('About to cache metric', [
                'name' => $name,
                'value' => $value,
                'tags' => $tags,
                'type' => $type,
                'unit' => $unit,
            ]);

            $cacheKey = $this->cacheMetric($name, $value, $tags, $type, $unit);

            $this->logger('Metric cached', ['cache_key' => $cacheKey]);

            if ($this->isLoggingEnabled()) {
                $this->logger('Logging enabled, calling logMetric');
                $this->logMetric($name, $value, $tags, $cacheKey);
            } else {
                $this->logger('Logging disabled, skipping logMetric');
            }

            $this->logger('Record method completed', ['cache_key' => $cacheKey]);

            return $cacheKey;
        } catch (Throwable $e) {
            throw new LametException('Error recording metric named: '.$name, previous: $e);
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

        // Check if we should ignore this database query
        if ($this->shouldIgnoreDbQuery($event)) {
            return;
        }

        $dbQueryConfig = $this->config['db_query'] ?? [];
        $metricName = $name ?? ($dbQueryConfig['metric_name'] ?? 'db.query');
        $shouldStoreOnlySlowQuery = $dbQueryConfig['store_only_slow_query'] ?? true;
        $tags = $additionalTags;

        // Calculate duration in milliseconds
        $duration = $event->time;
        $isQuerySlow = $duration >= ($dbQueryConfig['slow_query_threshold'] ?? 1500);
        // skip if only slow query store is enabled and the query is not slow then skip
        if ($shouldStoreOnlySlowQuery && ! $isQuerySlow) {
            return;
        }

        // Get SQL query
        $sql = $event->sql;

        // Get more accurate file and line information
        $file = 'unknown';
        $line = 0;

        // Use improved backtrace to find the actual source
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);

        // Look for the first non-Laravel, non-metrics file
        foreach ($backtrace as $trace) {
            if (isset($trace['file'])) {
                $filePath = $trace['file'];

                // Skip Laravel framework files
                if (str_contains($filePath, 'vendor/laravel/framework')) {
                    continue;
                }

                // Skip metrics-related files
                if (str_contains($filePath, 'MetricsManager.php') ||
                    str_contains($filePath, 'MetricsServiceProvider.php') ||
                    str_contains($filePath, 'HasMetrics')) {
                    continue;
                }

                // Skip other vendor files
                if (str_contains($filePath, 'vendor/')) {
                    continue;
                }

                // Found a relevant file
                $file = $filePath;
                $line = $trace['line'];
                break;
            }
        }

        // Add configured tags
        foreach ($dbQueryConfig['tags'] ?? [] as $tag) {
            switch ($tag) {
                case 'connection':
                    $tags['connection'] = $event->connectionName;
                    break;
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

        if ($isQuerySlow) {
            $metricName = $metricName.$dbQueryConfig['slow_query_name_suffix'] ?? '.slow';
            if ($shouldStoreOnlySlowQuery) {
                $this->record($metricName, $duration, $tags, 'timer', 'ms');

                return;
            }
        }

        $this->record($metricName, $duration, $tags, 'timer', 'ms');
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
        $traceLines = $exceptionConfig['trace_lines'] ?? 15;
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
                        $trace = str($throwable->getTraceAsString())->explode("\n")
                            ->take($traceLines)->implode("\n");
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
    public function clean(int $daysToKeep = 30, bool $dryRun = false): int|string
    {
        return $this->cleanOldMetrics($daysToKeep, $dryRun);
    }

    /**
     * Check if the current request path should be ignored.
     */
    protected function shouldIgnorePath(): bool
    {
        $this->logger('shouldIgnorePath method called');

        if (! $this->app->bound('request')) {
            $this->logger('Request not bound, returning false');

            return false;
        }

        $request = $this->app['request'];
        $currentPath = $request->path();
        $ignorePaths = $this->config['ignore']['paths'] ?? [];

        foreach ($ignorePaths as $ignorePath) {
            $ignorePath = trim($ignorePath, '/');
            $currentPath = trim($currentPath, '/');
            if ($this->pathMatches($currentPath, $ignorePath)) {
                $this->logger('Path matches ignore pattern', ['ignorePath' => $ignorePath]);

                return true;
            }
        }

        $this->logger('Path does not match any ignore patterns, returning false');

        return false;
    }

    /**
     * Check if an exception should be ignored.
     */
    protected function shouldIgnoreException(\Throwable $throwable): bool
    {
        $ignoreExceptions = $this->config['ignore']['exceptions'] ?? [];
        $ignoreExceptions[] = LametException::class;
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
        // Handle regex patterns like /^api\/v\d+\/.*$/
        if (str_starts_with($pattern, '/') && str_ends_with($pattern, '/')) {
            try {
                return preg_match($pattern, $path) === 1;
            } catch (\Exception $e) {
                // If regex is invalid, log it and fall back to exact match
                $this->logger('Invalid regex pattern in ignore paths', [
                    'pattern' => $pattern,
                    'error' => $e->getMessage(),
                ]);

                return $path === $pattern;
            }
        }

        // Handle wildcard patterns like /foo/*
        if (str_ends_with($pattern, '/*')) {
            $basePattern = rtrim($pattern, '/*');
            $matches = str_starts_with($path, $basePattern);

            return $matches;
        }

        // Exact match
        $matches = $path === $pattern;

        return $matches;
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
        // if (count($userFields) > 0) {
        //     $user = auth()->user();
        //     if ($user) {
        //         foreach ($userFields as $field) {
        //             $tags['user'][$field] = $user->{$field};
        //         }
        //     } else {
        //         unset($tags['user']);
        //     }
        // }

        // unset($tags['user']);

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

    /**
     * Check if a database query should be ignored.
     */
    protected function shouldIgnoreDbQuery(QueryExecuted $event): bool
    {
        $ignoreDbQuery = $this->config['ignore']['db_query'] ?? [];
        $sql = $event->sql;
        $userGetSql = 'select * from `users` where `id` = ? limit 1';
        if ($sql === $userGetSql) {
            return true;
        }

        // Check if query matches any ignored table names
        $ignoreTables = $ignoreDbQuery['tables'] ?? [];
        $ignoreTables[] = $this->config['table'] ?? 'metrics';
        foreach ($ignoreTables as $table) {
            if ($this->sqlContainsTable($sql, $table)) {
                return true;
            }
        }

        // Check if query matches any ignored SQL patterns
        $ignoreSqlPatterns = $ignoreDbQuery['sql_patterns'] ?? [];
        foreach ($ignoreSqlPatterns as $pattern) {
            if (preg_match($pattern, $sql)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if SQL query contains a specific table name.
     */
    protected function sqlContainsTable(string $sql, string $table): bool
    {
        // Normalize SQL for better matching
        $normalizedSql = strtolower(trim($sql));
        $tableName = strtolower($table);

        // Check for table name in various SQL contexts
        $patterns = [
            "/\bfrom\s+`?{$tableName}`?\b/i",
            "/\bjoin\s+`?{$tableName}`?\b/i",
            "/\binto\s+`?{$tableName}`?\b/i",
            "/\bupdate\s+`?{$tableName}`?\b/i",
            "/\bdelete\s+from\s+`?{$tableName}`?\b/i",
            "/\binsert\s+into\s+`?{$tableName}`?\b/i",
            "/\btruncate\s+`?{$tableName}`?\b/i",
            "/\bdrop\s+table\s+`?{$tableName}`?\b/i",
            "/\bcreate\s+table\s+`?{$tableName}`?\b/i",
            "/\balter\s+table\s+`?{$tableName}`?\b/i",
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $normalizedSql)) {
                return true;
            }
        }

        return false;
    }
}
