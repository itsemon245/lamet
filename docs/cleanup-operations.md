# Cleanup Operations

## Using the Facade

```php
use Itsemon245\Lamet\Facades\Metrics;

// Clean metrics older than 30 days (default)
$deletedCount = Metrics::clean();

// Clean metrics older than specified days
$deletedCount = Metrics::clean(90); // Keep last 90 days
```

## Using Helper Functions

```php
// Clean metrics older than 30 days (default)
$deletedCount = metricsClean();

// Clean metrics older than specified days
$deletedCount = metricsClean(90); // Keep last 90 days
```

## Command Line Usage

### Basic Cleanup

```bash
# Clean metrics older than 30 days
php artisan lamet:clean

# Clean metrics older than 90 days
php artisan lamet:clean --days=90

# Force cleanup without confirmation
php artisan lamet:clean --days=90 --force
```

### Scheduled Cleanup

```bash
# Add to your crontab or use Laravel's scheduler
php artisan lamet:clean --days=90 --force
```

## Configuration


### Scheduled Cleanup

Add to your `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    // Clean old metrics daily at 2 AM
    $schedule->command('lamet:clean --days=90 --force')
        ->dailyAt('02:00')
        ->withoutOverlapping();
}
```