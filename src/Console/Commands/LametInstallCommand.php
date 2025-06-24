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
        $this->line('   <comment>sqlite</comment>:');
        $this->line('   ```php');
        $this->line('   \'sqlite\' => [');
        $this->line('       \'driver\' => \'sqlite\',');
        $this->line('       \'database\' => storage_path(\'logs/lamet.sqlite\'),');
        $this->line('       \'prefix\' => \'\',');
        $this->line('   ],');
        $this->line('   ```');
        $this->newLine();
        $this->line('   <comment>pgsql</comment> (recommended):');
        $this->line('   ```php');
        $this->line('   \'pgsql\' => [');
        $this->line('       \'driver\' => \'pgsql\',');
        $this->line('       \'host\' => env(\'DB_HOST\', \'127.0.0.1\'),');
        $this->line('       \'port\' => env(\'DB_PORT\', \'5432\'),');
        $this->line('       \'database\' => env(\'DB_DATABASE\', \'lamet\'),');
        $this->line('       \'username\' => env(\'DB_USERNAME\', \'postgres\'),');
        $this->line('       \'password\' => env(\'DB_PASSWORD\', \'\'),');
        $this->line('       \'charset\' => \'utf8\',');
        $this->line('       \'prefix\' => \'\',');
        $this->line('       \'schema\' => \'public\',');
        $this->line('       \'sslmode\' => \'prefer\',');
        $this->line('   ],');
        $this->line('   ```');
        $this->newLine();

        $this->line('2. Set the database connection in your <comment>.env</comment> file:');
        $this->line('   <comment>LAMET_DB_CONNECTION=sqlite</comment> or <comment>LAMET_DB_CONNECTION=pgsql</comment>');
        $this->newLine();

        $this->line('3. Run the migrations:');
        $this->line('   <comment>php artisan migrate</comment>');
        $this->newLine();

        $this->line('4. Start recording metrics in your application:');
        $this->line('   <comment>use Itsemon245\\Lamet\\Facades\\Metrics;</comment>');
        $this->line('   <comment>Metrics::increment(\'api.requests\');</comment>');
        $this->newLine();

        $this->info('📚 For more information, check the README.md file.');
    }
}
