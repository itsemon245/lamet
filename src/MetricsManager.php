<?php

namespace Itsemon245\Metrics;

use Illuminate\Contracts\Foundation\Application;
use Itsemon245\Metrics\Traits\HasMetricsCache;
use Itsemon245\Metrics\Traits\HasMetricsDatabase;
use Itsemon245\Metrics\Traits\HasMetricsLogging;

class MetricsManager
{
    use HasMetricsCache, HasMetricsDatabase, HasMetricsLogging;

    /**
     * The application instance.
     */
    protected Application $app;

    /**
     * The metrics configuration.
     */
    protected array $config;

    /**
     * Create a new metrics manager instance.
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->config = $app['config']->get('metrics', []);
    }

    /**
     * Record a metric.
     */
    public function record(string $name, float $value, array $tags = [], ?string $type = null, ?string $unit = null): void
    {
        if (! $this->isEnabled()) {
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
     * Get default tags.
     */
    protected function getDefaultTags(): array
    {
        return $this->config['default_tags'] ?? [];
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
}
