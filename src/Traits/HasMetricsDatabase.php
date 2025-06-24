<?php

namespace Itsemon245\Metrics\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait HasMetricsDatabase
{
    /**
     * Store metrics in the database.
     */
    protected function storeMetricsInDatabase(array $metrics): void
    {
        if (empty($metrics)) {
            return;
        }

        $connection = $this->config['drivers']['database']['connection'] ?? 'sqlite';
        $tableName = $this->config['drivers']['database']['table'] ?? 'metrics';
        $batchSize = $this->getCacheBatchSize();

        try {
            // Process metrics in batches
            $chunks = array_chunk($metrics, $batchSize);

            foreach ($chunks as $chunk) {
                $this->insertMetricsBatch($connection, $tableName, $chunk);
            }

            Log::info('Stored '.count($metrics).' metrics in database');
        } catch (\Exception $e) {
            Log::error('Failed to store metrics in database: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Insert a batch of metrics into the database.
     */
    protected function insertMetricsBatch(string $connection, string $tableName, array $metrics): void
    {
        $data = [];

        foreach ($metrics as $metric) {
            $data[] = [
                'name' => $metric['name'],
                'value' => $metric['value'],
                'tags' => json_encode($metric['tags'] ?? []),
                'type' => $this->determineMetricType($metric),
                'unit' => $this->determineMetricUnit($metric),
                'recorded_at' => $metric['timestamp'] ?? now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::connection($connection)->table($tableName)->insert($data);
    }

    /**
     * Determine metric type based on name and value.
     */
    protected function determineMetricType(array $metric): string
    {
        // Default to 'counter' for current plan
        return 'counter';
    }

    /**
     * Determine metric unit based on name and value.
     */
    protected function determineMetricUnit(array $metric): ?string
    {
        // Default to null for current plan
        return null;
    }

    /**
     * Get metrics from database.
     */
    protected function getMetricsFromDatabase(array $filters = []): array
    {
        $connection = $this->config['drivers']['database']['connection'] ?? 'sqlite';
        $tableName = $this->config['drivers']['database']['table'] ?? 'metrics';

        $query = DB::connection($connection)->table($tableName);

        // Apply filters
        if (isset($filters['name'])) {
            $query->where('name', $filters['name']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['from'])) {
            $query->where('recorded_at', '>=', $filters['from']);
        }

        if (isset($filters['to'])) {
            $query->where('recorded_at', '<=', $filters['to']);
        }

        return $query->orderBy('recorded_at', 'desc')->get()->toArray();
    }

    /**
     * Clean old metrics from database.
     */
    protected function cleanOldMetrics(int $daysToKeep = 30): int
    {
        $connection = $this->config['drivers']['database']['connection'] ?? 'sqlite';
        $tableName = $this->config['drivers']['database']['table'] ?? 'metrics';

        $cutoffDate = now()->subDays($daysToKeep);

        $deleted = DB::connection($connection)
            ->table($tableName)
            ->where('recorded_at', '<', $cutoffDate)
            ->delete();

        Log::info("Cleaned {$deleted} old metrics from database");

        return $deleted;
    }
}
