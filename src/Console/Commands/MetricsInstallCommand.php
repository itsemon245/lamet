<?php

namespace Itsemon245\Metrics\Console\Commands;

use Illuminate\Console\Command;

class MetricsInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'metrics:install {--force : Overwrite existing files}';

    /**
     * The console command description.
     */
    protected $description = 'Install the Laravel Metrics package';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Installing Laravel Metrics package...');

        // Publish configuration
        $this->info('Publishing configuration...');
        $this->call('vendor:publish', [
            '--tag' => 'metrics-config',
            '--force' => $this->option('force'),
        ]);

        // Publish migrations
        $this->info('Publishing migrations...');
        $this->call('vendor:publish', [
            '--tag' => 'metrics-migrations',
            '--force' => $this->option('force'),
        ]);

        $this->newLine();
        $this->info('✅ Laravel Metrics package installed successfully!');
        $this->newLine();

        // Display next steps
        $this->displayNextSteps();

        return self::SUCCESS;
    }

    /**
     * Display the next steps for setup.
     */
    protected function displayNextSteps(): void
    {
        $this->info('📋 Next Steps:');
        $this->newLine();

        $this->line('1. Configure your database connection in <comment>config/database.php</comment>:');
        $this->newLine();
        $this->line('   <comment>sqlite</comment> (recommended for metrics):');
        $this->line('   ```php');
        $this->line('   \'sqlite\' => [');
        $this->line('       \'driver\' => \'sqlite\',');
        $this->line('       \'database\' => storage_path(\'logs/metrics.sqlite\'),');
        $this->line('       \'prefix\' => \'\',');
        $this->line('   ],');
        $this->line('   ```');
        $this->newLine();

        $this->line('2. Set the database connection in your <comment>.env</comment> file:');
        $this->line('   <comment>METRICS_DB_CONNECTION=sqlite</comment>');
        $this->newLine();

        $this->line('3. Run the migrations:');
        $this->line('   <comment>php artisan migrate</comment>');
        $this->newLine();

        $this->line('4. Start recording metrics in your application:');
        $this->line('   <comment>use Itsemon245\\Metrics\\Facades\\Metrics;</comment>');
        $this->line('   <comment>Metrics::increment(\'api.requests\');</comment>');
        $this->newLine();

        $this->info('📚 For more information, check the README.md file.');
    }
}
