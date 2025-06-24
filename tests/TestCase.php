<?php

namespace Itsemon245\Lamet\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Itsemon245\Lamet\MetricsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

uses(Orchestra::class, RefreshDatabase::class)->in('.');

abstract class TestCase extends Orchestra
{
    /**
     * Get package providers.
     */
    protected function getPackageProviders($app): array
    {
        return [
            MetricsServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     */
    protected function defineEnvironment($app): void
    {
        // Use SQLite in-memory for testing
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Use array cache for testing
        $app['config']->set('cache.default', 'array');
        $app['config']->set('cache.stores.array', [
            'driver' => 'array',
        ]);

        // Configure Lamet for testing
        $app['config']->set('lamet.enabled', true);
        $app['config']->set('lamet.log_metrics', false);
        $app['config']->set('lamet.table', 'lamet');
        $app['config']->set('lamet.connection', 'testing');
        $app['config']->set('lamet.cache.store', 'array');
        $app['config']->set('lamet.cache.prefix', 'lamet_test:');
        $app['config']->set('lamet.cache.ttl', 3600);
        $app['config']->set('lamet.cache.batch_size', 100);
        $app['config']->set('lamet.cache.flush_interval', 300);
        $app['config']->set('lamet.default_tags', [
            'environment' => 'testing',
            'app_name' => 'lamet-test',
        ]);
    }

    /**
     * Define database migrations.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * Get the metrics table name for tests (from config, env, or default).
     */
    protected function getMetricsTableName(): string
    {
        // Try config first (Laravel config helper)
        $table = config('lamet.table');
        if ($table) {
            return $table;
        }
        // Try environment variable
        $envTable = env('LAMET_TABLE');
        if ($envTable) {
            return $envTable;
        }

        // Fallback
        return 'metrics';
    }
}
