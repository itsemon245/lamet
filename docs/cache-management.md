# Cache Management

## Using the Facade

```php
use Itsemon245\Lamet\Facades\Metrics;

// Flush cached metrics to database
$flushedCount = Metrics::flush();

// Get cache configuration
$config = Metrics::getConfig();
```

## Using Helper Functions

```php
// Flush cached metrics to database
$flushedCount = metricsFlush();
```

## Understanding the Cache System

The package uses a caching system to improve performance:

1. **Metrics are stored in cache first** (Redis, Memcached, etc.)
2. **Periodic flushing** moves metrics to the database
3. **Batch processing** reduces database load
4. **Automatic aggregation** of similar metrics

## Configuration

Cache settings are configured in `config/lamet.php`:

```php
'cache' => [
    'store' => env('LAMET_CACHE_STORE', 'redis'),
    'prefix' => env('LAMET_CACHE_PREFIX', 'lamet:'),
    'ttl' => env('LAMET_CACHE_TTL', 3600),
    'batch_size' => env('LAMET_CACHE_BATCH_SIZE', 1000),
],
```

## Manual Flushing

### Command Line

```bash
# Flush all cached metrics
php artisan lamet:flush

# Force flush even if cache is disabled
php artisan lamet:flush --force
```

### Programmatic Flushing

```php
// Flush and get count of flushed metrics
$count = Metrics::flush();

// Check if flush was successful
if ($count > 0) {
    Log::info("Flushed {$count} metrics to database");
}
```

## Scheduled Flushing

Add to your `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    // Flush every 5 minutes
    $schedule->command('lamet:flush')->everyFiveMinutes();

    // Or flush every minute for high-traffic applications
    $schedule->command('lamet:flush')->everyMinute();
}
```

## Cache Performance

### High-Traffic Applications

```php
// More frequent flushing for high-traffic apps
'cache' => [
    'ttl' => 300, // 5 minutes
    'batch_size' => 500, // Smaller batches
],
```

### Low-Traffic Applications

```php
// Less frequent flushing for low-traffic apps
'cache' => [
    'ttl' => 7200, // 2 hours
    'batch_size' => 2000, // Larger batches
],
```

## Monitoring Cache Health

```php
// Check cache status
$config = Metrics::getConfig();
$cacheEnabled = !empty($config['cache']['store']);

// Monitor cache performance
Metrics::record('cache.flush.duration', $flushTime, [
    'batch_size' => $flushedCount,
], 'timer', 'ms');
```

## Troubleshooting

### Cache Not Flushing

```php
// Check if cache is enabled
if (config('lamet.cache.store')) {
    // Cache is enabled, check TTL and batch size
    $ttl = config('lamet.cache.ttl');
    $batchSize = config('lamet.cache.batch_size');
}
```

### High Memory Usage

```php
// Reduce TTL for more frequent flushing
'cache' => [
    'ttl' => 300, // 5 minutes instead of 1 hour
],
```

### Database Connection Issues

```php
// Implement retry logic for flush operations
try {
    $count = Metrics::flush();
} catch (Exception $e) {
    Log::error('Failed to flush metrics', ['error' => $e->getMessage()]);
    // Implement retry mechanism
}
```
