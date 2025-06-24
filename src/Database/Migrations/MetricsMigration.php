<?php

namespace Itsemon245\Metrics\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

abstract class MetricsMigration extends Migration
{
    /**
     * Get the database connection that should be used for the migration.
     */
    public function getConnection(): ?string
    {
        return config('metrics.drivers.database.connection', 'sqlite');
    }

    /**
     * Get the schema builder for the metrics connection.
     */
    final public function getMetricsSchema()
    {
        $connection = $this->getConnection();

        if (! array_key_exists($connection, config('database.connections', []))) {
            throw new \Exception("Connection {$connection} not found in database.connections");
        }

        return Schema::connection($connection);
    }

    /**
     * Get the table name from config.
     */
    final public function getTableName(): string
    {
        return config('metrics.drivers.database.table', 'metrics');
    }

    /**
     * Check if the current database is PostgreSQL.
     */
    final public function isPostgreSQL(): bool
    {
        $connection = $this->getConnection();
        $driver = config("database.connections.{$connection}.driver");

        return $driver === 'pgsql';
    }

    /**
     * Check if the current database is MySQL.
     */
    final public function isMySQL(): bool
    {
        $connection = $this->getConnection();
        $driver = config("database.connections.{$connection}.driver");

        return $driver === 'mysql';
    }

    /**
     * Check if the current database is SQLite.
     */
    final public function isSQLite(): bool
    {
        $connection = $this->getConnection();
        $driver = config("database.connections.{$connection}.driver");

        return $driver === 'sqlite';
    }

    /**
     * Add JSON column with appropriate type for the database.
     */
    protected function addJsonColumn(Blueprint $table, string $columnName, bool $nullable = true): void
    {
        if ($this->isPostgreSQL()) {
            $table->jsonb($columnName)->nullable($nullable);
        } else {
            $table->json($columnName)->nullable($nullable);
        }
    }

    /**
     * Add GIN index for JSON column (PostgreSQL only).
     */
    protected function addJsonIndex(Blueprint $table, string $columnName, ?string $indexName = null): void
    {
        if ($this->isPostgreSQL()) {
            $indexName = $indexName ?: "{$columnName}_gin_index";
            $table->index($columnName, $indexName, 'gin');
        }
    }
}
