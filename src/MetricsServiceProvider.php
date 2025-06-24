<?php

namespace Itsemon245\Lamet;

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
            __DIR__.'/../config/lamet.php', 'lamet'
        );

        $this->app->singleton('lamet', function ($app) {
            return new MetricsManager($app);
        });

        $this->app->alias('lamet', MetricsManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/lamet.php' => config_path('lamet.php'),
            ], 'lamet-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations/lamet'),
            ], 'lamet-migrations');

            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

            // Register commands
            $this->commands([
                Console\Commands\LametInstallCommand::class,
                Console\Commands\LametFlushCommand::class,
                Console\Commands\LametCleanCommand::class,
            ]);
        }

        // Register the facade
        Facade::clearResolvedInstances();
    }
}
