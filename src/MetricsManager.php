<?php

namespace Itsemon245\Metrics;

use Illuminate\Contracts\Foundation\Application;
use Itsemon245\Metrics\Traits\HasMetricsCache;
use Itsemon245\Metrics\Traits\HasMetricsDatabase;

class MetricsManager
{
    use HasMetricsCache, HasMetricsDatabase;

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
    public function record(string $name, float $value, array $tags = []): void
    {
        // Add default tags
        $tags = array_merge($this->getDefaultTags(), $tags);
        $this->cacheMetric($name, $value, $tags);
    }

    /**
     * Increment a counter.
     */
    public function increment(string $name, int $value = 1, array $tags = []): void
    {
        $this->record($name, $value, $tags);
    }

    /**
     * Decrement a counter.
     */
    public function decrement(string $name, int $value = 1, array $tags = []): void
    {
        $this->record($name, -$value, $tags);
    }

    /**
     * Time a function execution.
     */
    public function time(string $name, callable $callback, array $tags = []): mixed
    {
        $start = microtime(true);
        try {
            $result = $callback();
            $this->record($name, (microtime(true) - $start) * 1000, $tags);

            return $result;
        } catch (\Exception $e) {
            $this->record($name, (microtime(true) - $start) * 1000, array_merge($tags, ['error' => true]));
            throw $e;
        }
    }

    /**
     * Flush cached metrics to database.
     */
    public function flush(): int
    {
        $metrics = $this->getCachedMetrics();
        if (empty($metrics)) {
            return 0;
        }
        $this->storeMetricsInDatabase($metrics);
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
}
