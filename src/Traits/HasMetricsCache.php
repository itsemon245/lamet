<?php

namespace Itsemon245\Lamet\Traits;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

trait HasMetricsCache
{
    protected ?Repository $cacheStore = null;

    const UNSAVED_KEYS_CONSTANT = 'unsaved_keys';

    private function getUnsavedKeyConstant(): string
    {
        $prefix = $this->getCachePrefix();

        return $prefix.self::UNSAVED_KEYS_CONSTANT;
    }

    /**
     * Store a metric in cache.
     */
    protected function cacheMetric(string $name, float $value, array $tags = [], string $type = 'counter', ?string $unit = null): string
    {
        $cacheKey = $this->buildCacheKey($name, $tags, $type, $unit);

        $this->logger('Cache key built', ['cache_key' => $cacheKey]);

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
        $existing = $this->cacheStore->get($cacheKey);

        if ($existing) {
            // Aggregate with existing metric
            $metric['value'] = $existing['value'] + $value;
            $metric['count'] = $existing['count'] + 1;
            $metric['timestamp'] = $existing['timestamp']; // Keep original timestamp

            $this->logger('Metric aggregated with existing', ['updated_metric' => $metric]);
        }

        // Store in cache with TTL
        $this->cacheStore->put(
            $cacheKey,
            $metric,
            $this->getCacheTtl()
        );

        $this->logger('Metric stored in cache');

        $this->setUnsavedKeys($cacheKey);

        return $cacheKey;
    }

    /**
     * Update unsaved keys array in the cache
     */
    protected function setUnsavedKeys(string $storedCacheKey)
    {
        /**
         * @var array|string|null $unsavedKeys
         */
        $unsavedKeys = $this->getUnsavedKeys();
        if (empty($unsavedKeys)) {
            $unsavedKeys = [];
        }
        if (is_string($unsavedKeys)) {
            $unsavedKeys = [$unsavedKeys];
        }
        $this->logger('Unsaved Metric Keys', ['keys' => $unsavedKeys]);

        if (in_array($storedCacheKey, $unsavedKeys)) {
            $this->logger('Unsaved key already exists, skipping', ['key' => $storedCacheKey]);

            return;
        }

        $unsavedKeys[] = $storedCacheKey;

        $this->cacheStore->put(
            self::UNSAVED_KEYS_CONSTANT,
            $unsavedKeys,
            $this->getCacheTtl()
        );
        $this->logger('Unsaved keys updated', ['new_key' => $storedCacheKey]);
    }

    /**
     * Get unsaved keys from the cache
     *
     * @return array
     */
    public function getUnsavedKeys()
    {
        return $this->cacheStore->get(self::UNSAVED_KEYS_CONSTANT) ?? [];
    }

    /**
     * Delete unsaved keys from the cache and return them
     */
    public function deleteUnsavedKeys(): array
    {
        $keys = $this->getUnsavedKeys();
        $this->cacheStore->forget(self::UNSAVED_KEYS_CONSTANT);

        return $keys;
    }

    /**
     * Get all cached metrics.
     */
    protected function getCachedMetrics(): array
    {
        $store = $this->cacheStore;
        $prefix = $this->getCachePrefix();
        $metrics = [];
        $keys = $this->getCacheKeys();
        foreach ($keys as $key) {
            $metric = $store->get($key);
            if ($metric) {
                $metrics[] = $metric;
            }
        }

        return $metrics;
    }

    /**
     * Clear all cached metrics and return cleared keys
     */
    protected function clearCachedMetrics(): array
    {
        $keys = $this->getCacheKeys();
        foreach ($keys as $key) {
            $this->cacheStore->forget($key);
        }

        $clearedKeys = $this->deleteUnsavedKeys();
        $this->logger('Cleared keys', ['keys' => $clearedKeys]);

        return $clearedKeys;
    }

    /**
     * Build cache key for a metric.
     */
    protected function buildCacheKey(string $name, array $tags = [], string $type = 'counter', ?string $unit = null): string
    {
        $prefix = $this->getCachePrefix();
        $nameString = $name ? ":$name" : '';
        $unitString = $unit ? ":$unit" : '';
        $tagString = empty($tags) ? '' : ':'.md5(serialize($tags));

        $cacheKey = $prefix.$type.$nameString.$unitString.$tagString;

        return $cacheKey;
    }

    /**
     * Get cache keys with prefix (simplified implementation).
     */
    protected function getCacheKeys(): array
    {
        return $this->getUnsavedKeys();
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
