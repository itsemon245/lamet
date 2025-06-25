<?php

namespace Itsemon245\Lamet\Traits;

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

        $connection = $this->config['connection'] ?? null;
        $tableName = $this->config['table'] ?? 'metrics';
        if (! $connection) {
            // If no connection is set, do not store metrics
            return;
        }
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
                'type' => $metric['type'] ?? 'counter',
                'unit' => $metric['unit'] ?? null,
                'recorded_at' => $metric['timestamp'] ?? now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::connection($connection)->table($tableName)->insert($data);
    }

    /**
     * Get metrics from database.
     */
    protected function getMetricsFromDatabase(array $filters = []): array
    {
        $connection = $this->config['connection'] ?? null;
        $tableName = $this->config['table'] ?? 'metrics';
        if (! $connection) {
            return [];
        }
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
    protected function cleanOldMetrics(int $daysToKeep = 30, bool $dryRun = false): int|string
    {
        $connection = $this->config['connection'] ?? null;
        $tableName = $this->config['table'] ?? 'metrics';
        if (! $connection) {
            return 0;
        }
        $cutoffDate = now()->subDays($daysToKeep);

        $query = DB::connection($connection)
            ->table($tableName)
            ->where('recorded_at', '<', $cutoffDate);

        if ($dryRun) {
            $this->logger('Dry run mode enabled, no metrics will be deleted');
            $statement = $query->toSql(). ", ".json_encode($query->getBindings());
            return $statement;
        }

        $deleted = $query->delete();
        Log::info("Cleaned {$deleted} old metrics from database");

        return $deleted;
    }
}
