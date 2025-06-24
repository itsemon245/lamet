<?php

namespace Itsemon245\Metrics;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

class MetricsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/metrics.php', 'metrics'
        );

        $this->app->singleton('metrics', function ($app) {
            return new MetricsManager($app);
        });

        $this->app->alias('metrics', MetricsManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/metrics.php' => config_path('metrics.php'),
            ], 'metrics-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations/metrics'),
            ], 'metrics-migrations');

            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

            // Register commands
            $this->commands([
                Console\Commands\MetricsInstallCommand::class,
                Console\Commands\MetricsFlushCommand::class,
                Console\Commands\MetricsCleanCommand::class,
            ]);
        }

        // Register the facade
        Facade::clearResolvedInstances();
    }
}
