<?php

namespace Itsemon245\Metrics\Console\Commands;

use Illuminate\Console\Command;
use Itsemon245\Metrics\MetricsManager;

class MetricsCleanCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'metrics:clean {--days=30 : Number of days to keep} {--force : Force the operation}';

    /**
     * The console command description.
     */
    protected $description = 'Clean old metrics from database';

    /**
     * Execute the console command.
     */
    public function handle(MetricsManager $metrics): int
    {
        $days = (int) $this->option('days');
        $force = $this->option('force');

        if (! $force) {
            if (! $this->confirm("This will delete metrics older than {$days} days. Are you sure?")) {
                $this->info('Operation cancelled.');

                return self::SUCCESS;
            }
        }

        $this->info("Cleaning metrics older than {$days} days...");

        try {
            $deleted = $metrics->clean($days);

            if ($deleted > 0) {
                $this->info("✅ Successfully cleaned {$deleted} old metrics from database");
            } else {
                $this->info('ℹ️  No old metrics found to clean');
            }

        } catch (\Exception $e) {
            $this->error('Failed to clean metrics: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
