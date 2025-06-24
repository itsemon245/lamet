<?php

namespace Itsemon245\Lamet\Console\Commands;

use Illuminate\Console\Command;
use Itsemon245\Lamet\MetricsManager;

class LametFlushCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'lamet:flush {--force : Force flush even if cache is disabled}';

    /**
     * The console command description.
     */
    protected $description = 'Flush cached metrics to database';

    /**
     * Execute the console command.
     */
    public function handle(MetricsManager $metrics): int
    {
        $this->info('Flushing cached metrics to database...');

        try {
            $count = $metrics->flush();

            if ($count > 0) {
                $this->info("✅ Successfully flushed {$count} metrics to database");
            } else {
                $this->info('ℹ️  No cached metrics found to flush');
            }

        } catch (\Exception $e) {
            $this->error('Failed to flush metrics: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
