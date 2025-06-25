<?php

namespace Itsemon245\Lamet\Console\Commands;

use Illuminate\Console\Command;
use Itsemon245\Lamet\MetricsManager;

class LametFlushCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'lamet:flush 
    {--force : Force flush even if cache is disabled}
    {--dry-run : Dry run the command}
    {--P|print : Prints the unsaved keys}
    ';

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
            $unsavedKeys = $metrics->getUnsavedKeys();
            $unsavedKeysCount = count($unsavedKeys);
            if ($this->option('dry-run')) {
                $this->info('Dry run mode enabled, no metrics will be flushed');
                $this->info("Found {$unsavedKeysCount} unsaved keys");
                $this->printUnsavedKeys($unsavedKeys);

                return self::SUCCESS;
            }

            $count = $metrics->flush();

            if ($count > 0) {
                $this->info("✅ Successfully flushed {$count} metrics to database");
                $this->printUnsavedKeys($unsavedKeys);
            } else {
                $this->info('ℹ️  No cached metrics found to flush');
            }

        } catch (\Exception $e) {
            $this->error('Failed to flush metrics: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function printUnsavedKeys(array $keys): void
    {
        if ($this->option('print')) {
            if (count($keys) <= 0) {
                return;
            }
            $this->info('Unsaved keys:');
            $this->info(json_encode($keys, JSON_PRETTY_PRINT));
        }
    }
}
