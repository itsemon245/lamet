<?php

namespace Itsemon245\Metrics\Traits;

use Illuminate\Support\Facades\Cache;

trait HasMetricsCache
{
    /**
     * Store a metric in cache.
     */
    protected function cacheMetric(string $name, float $value, array $tags = []): void
    {
        $cacheKey = $this->buildCacheKey($name, $tags);
        $metric = [
            'name' => $name,
            'value' => $value,
            'tags' => $tags,
            'timestamp' => now()->toISOString(),
            'count' => 1,
        ];

        // Try to get existing metric from cache
        $existing = Cache::store($this->getCacheStore())->get($cacheKey);

        if ($existing) {
            // Aggregate with existing metric
            $metric['value'] = $existing['value'] + $value;
            $metric['count'] = $existing['count'] + 1;
            $metric['timestamp'] = $existing['timestamp']; // Keep original timestamp
        }

        // Store in cache with TTL
        Cache::store($this->getCacheStore())->put(
            $cacheKey,
            $metric,
            $this->getCacheTtl()
        );
    }

    /**
     * Get all cached metrics.
     */
    protected function getCachedMetrics(): array
    {
        $store = Cache::store($this->getCacheStore());
        $prefix = $this->getCachePrefix();
        $metrics = [];

        // This is a simplified approach - in production you might want to use
        // Redis SCAN or similar for better performance with large datasets
        $keys = $this->getCacheKeys($prefix);

        foreach ($keys as $key) {
            $metric = $store->get($key);
            if ($metric) {
                $metrics[] = $metric;
            }
        }

        return $metrics;
    }

    /**
     * Clear all cached metrics.
     */
    protected function clearCachedMetrics(): void
    {
        $prefix = $this->getCachePrefix();
        $keys = $this->getCacheKeys($prefix);

        foreach ($keys as $key) {
            Cache::store($this->getCacheStore())->forget($key);
        }
    }

    /**
     * Build cache key for a metric.
     */
    protected function buildCacheKey(string $name, array $tags = []): string
    {
        $prefix = $this->getCachePrefix();
        $tagString = empty($tags) ? '' : ':'.md5(serialize($tags));

        return $prefix.$name.$tagString;
    }

    /**
     * Get cache keys with prefix (simplified implementation).
     */
    protected function getCacheKeys(string $prefix): array
    {
        // This is a simplified approach - in production with Redis you'd use SCAN
        // For now, we'll return an empty array and let the database driver handle it
        return [];
    }

    /**
     * Get cache store name.
     */
    protected function getCacheStore(): string
    {
        return $this->config['cache']['store'] ?? 'redis';
    }

    /**
     * Get cache prefix.
     */
    protected function getCachePrefix(): string
    {
        return $this->config['cache']['prefix'] ?? 'metrics:';
    }

    /**
     * Get cache TTL.
     */
    protected function getCacheTtl(): int
    {
        return $this->config['cache']['ttl'] ?? 3600;
    }

    /**
     * Get cache batch size.
     */
    protected function getCacheBatchSize(): int
    {
        return $this->config['cache']['batch_size'] ?? 1000;
    }
}
