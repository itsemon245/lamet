<?php

namespace Itsemon245\Lamet\Tests\Feature;

use Itsemon245\Lamet\Tests\TestCase;
use Illuminate\Support\Facades\File;

class CommandsTest extends TestCase
{
    public function test_lamet_install_command()
    {
        // Remove config file if it exists
        if (File::exists(config_path('lamet.php'))) {
            File::delete(config_path('lamet.php'));
        }
        
        $this->artisan('lamet:install')
            ->expectsOutput('Installing Laravel Lamet package...')
            ->expectsOutput('Publishing configuration...')
            ->expectsOutput('Publishing migrations...')
            ->expectsOutput('✅ Laravel Lamet package installed successfully!')
            ->assertExitCode(0);
        
        // Check if config file was published
        $this->assertTrue(File::exists(config_path('lamet.php')));
    }

    public function test_lamet_install_command_with_force()
    {
        $this->artisan('lamet:install', ['--force' => true])
            ->expectsOutput('Installing Laravel Lamet package...')
            ->assertExitCode(0);
    }

    public function test_lamet_flush_command()
    {
        // Record some metrics first
        \Itsemon245\Lamet\Facades\Metrics::increment('test.counter');
        
        $this->artisan('lamet:flush')
            ->expectsOutput('Flushing cached metrics to database...')
            ->assertExitCode(0);
    }

    public function test_lamet_flush_command_with_force()
    {
        $this->artisan('lamet:flush', ['--force' => true])
            ->expectsOutput('Flushing cached metrics to database...')
            ->assertExitCode(0);
    }

    public function test_lamet_clean_command()
    {
        $this->artisan('lamet:clean', ['--days' => 30, '--force' => true])
            ->expectsOutput('Cleaning metrics older than 30 days...')
            ->assertExitCode(0);
    }

    public function test_lamet_clean_command_with_confirmation()
    {
        $this->artisan('lamet:clean', ['--days' => 30])
            ->expectsConfirmation('This will delete metrics older than 30 days. Are you sure?', 'no')
            ->expectsOutput('Operation cancelled.')
            ->assertExitCode(0);
    }
} 