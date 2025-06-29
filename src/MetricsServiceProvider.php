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
                __DIR__.'/../config/lamet.php' => config_path('lamet.php'),
            ], 'lamet-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
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

        // Register automatic event listeners
        $this->registerEventListeners();
    }

    /**
     * Register automatic event listeners for metrics tracking.
     */
    protected function registerEventListeners(): void
    {
        $config = $this->app['config']->get('lamet', []);

        // Register database query listener if enabled
        if ($config['db_query']['enabled'] ?? true) {
            $this->app['events']->listen(
                \Illuminate\Database\Events\QueryExecuted::class,
                function (\Illuminate\Database\Events\QueryExecuted $event) {
                    $this->app['metrics']->dbQuery($event);
                }
            );
        }

        // Register exception listener if enabled
        if ($config['exception']['enabled'] ?? false) {
            $this->app['events']->listen(
                \Illuminate\Log\Events\MessageLogged::class,
                function (\Illuminate\Log\Events\MessageLogged $event) {
                    if ($event->level === 'error' && $event->context['exception'] ?? null) {
                        $this->app['metrics']->exception($event->context['exception']);
                    }
                }
            );
        }
    }
}
