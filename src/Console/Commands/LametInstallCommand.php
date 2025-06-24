<?php

namespace Itsemon245\Lamet\Console\Commands;

use Illuminate\Console\Command;

class LametInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'lamet:install {--force : Overwrite existing files}';

    /**
     * The console command description.
     */
    protected $description = 'Install the Laravel Lamet package';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Installing Laravel Lamet package...');

        // Publish configuration
        $this->info('Publishing configuration...');
        $this->call('vendor:publish', [
            '--tag' => 'lamet-config',
            '--force' => $this->option('force'),
        ]);

        // Publish migrations
        $this->info('Publishing migrations...');
        $this->call('vendor:publish', [
            '--tag' => 'lamet-migrations',
            '--force' => $this->option('force'),
        ]);

        $this->newLine();
        $this->info('✅ Laravel Lamet package installed successfully!');
        $this->newLine();
        $this->info("Please follow the instructions in the README.md file to complete the installation.");
        $this->newLine();

        return self::SUCCESS;
    }
}
