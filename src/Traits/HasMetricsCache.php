<?php

namespace Itsemon245\Lamet\Traits;

use Illuminate\Support\Facades\Cache;

trait HasMetricsCache
{
    /**
     * Store a metric in cache.
     */
    protected function cacheMetric(string $name, float $value, array $tags = [], string $type = 'counter', ?string $unit = null): void
    {
        $cacheKey = $this->buildCacheKey($name, $tags, $type, $unit);
        $metric = [
            'name' => $name,
            'value' => $value,
            'tags' => $tags,
            'type' => $type,
            'unit' => $unit,
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
    protected function buildCacheKey(string $name, array $tags = [], string $type = 'counter', ?string $unit = null): string
    {
        $prefix = $this->getCachePrefix();
        $tagString = empty($tags) ? '' : ':'.md5(serialize($tags));
        $typeString = $type ? ":$type" : '';
        $unitString = $unit ? ":$unit" : '';

        return $prefix.$name.$tagString.$typeString.$unitString;
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
        return $this->config['cache']['prefix'] ?? 'lamet:';
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
