<?php

namespace Itsemon245\Lamet\Traits;

use Illuminate\Support\Facades\Log;

trait HasMetricsLogging
{
    /**
     * Log a metric to Laravel's log system.
     */
    protected function logMetric(string $name, float $value, array $tags = []): void
    {
        if (! $this->isLoggingEnabled()) {
            return;
        }

        $logData = [
            'name' => $name,
            'value' => $value,
            'tags' => $tags,
            'timestamp' => now()->toISOString(),
        ];

        $channel = $this->getLogChannel();

        Log::channel($channel)->info('Metric recorded', $logData);
    }

    /**
     * Log multiple metrics in batch.
     */
    protected function logMetricsBatch(array $metrics): void
    {
        if (! $this->isLoggingEnabled() || empty($metrics)) {
            return;
        }

        $channel = $this->getLogChannel();

        Log::channel($channel)->info('Metrics batch recorded', [
            'count' => count($metrics),
            'metrics' => $metrics,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Check if logging is enabled.
     */
    protected function isLoggingEnabled(): bool
    {
        return $this->config['log_metrics'] ?? false;
    }

    /**
     * Get log channel.
     */
    protected function getLogChannel(): string
    {
        return $this->config['drivers']['log']['channel'] ?? 'daily';
    }
}
