# Laravel Metrics

A simple, high-performance package to record and aggregate metrics in Laravel applications, ready for Grafana dashboards.

## 🚀 Installation

1. **Install via Composer:**

   ```bash
   composer require itsemon245/laravel-metrics
   ```

2. **Publish the configuration and migration:**

   ```bash
   php artisan metrics:install
   ```

   This will publish `config/metrics.php` and the migration to `database/migrations/metrics/`.

3. **Configure your database and cache:**

   - Set up your desired database connection in `config/database.php` (e.g., `sqlite`, `mysql`, `pgsql`). _Postgres is recommended_
   - Set up your cache store in `config/cache.php` (e.g., `redis`).
   - Update `config/metrics.php` to reference the correct `store`, `table`, and `connection` names.

4. **Run the migration:**

   ```bash
   php artisan migrate
   ```

5. **(Optional) Schedule periodic flushing:**
   In `app/Console/Kernel.php`:
   ```php
   protected function schedule(Schedule $schedule): void
   {
       $schedule->command('metrics:flush')->everyFiveMinutes();
   }
   ```

## ✨ Usage

### Using the Facade

```php
use Itsemon245\Metrics\Facades\Metrics;

// Record a metric (e.g., exception occurrence)
Metrics::increment('exception.occurrence', 1, [
    'exception_class' => get_class($exception),
    'file' => $exception->getFile(),
    'line' => $exception->getLine(),
    'endpoint' => request()->path(),
    'method' => request()->method(),
]);

// Record HTTP request metrics in middleware
Metrics::increment('http.requests', 1, [
    'endpoint' => $request->path(),
    'method' => $request->method(),
    'status_code' => $response->getStatusCode(),
]);

// Time a function execution
$result = Metrics::time('database.query', function () {
    return User::where('active', true)->get();
}, ['table' => 'users']);

// Decrement a counter
Metrics::decrement('active.users', 1);
```

### Using Helper Functions

```php
// Record a metric
metrics('user.login', 1, ['user_id' => 123]);

// Increment a counter (defaults to 1)
metrics('api.requests', tags: ['endpoint' => '/users']);

// Time a function execution
$result = metrics_time('database.query', function () {
    return User::where('active', true)->get();
}, ['table' => 'users']);

// Increment/decrement helpers
metrics_increment('active.users');
metrics_decrement('active.users');

// Flush cached metrics
metrics_flush();

// Get metrics from database
$metrics = metrics_get(['name' => 'api.requests']);

// Clean old metrics
metrics_clean(30); // Keep last 30 days
```

### Using Dependency Injection

```php
use Itsemon245\Metrics\MetricsManager;

class UserController extends Controller
{
    public function __construct(private MetricsManager $metrics)
    {
    }

    public function index()
    {
        $this->metrics->increment('user.list.viewed');

        return User::paginate();
    }
}
```

---

For advanced usage and query examples, see:

- [Query Examples](docs/query-examples.md) - Common SQL queries for Grafana
- [Advanced Usage](docs/advance-usage.md) - Advanced metric types and patterns
- [Grafana Queries](docs/grafana-queries.md) - Grafana-specific query examples

## 📝 Notes

- The `tags` column is flexible and can store any key-value pairs.
- The `recorded_at` column is used for time-series queries in Grafana.
- The `type` column defaults to `counter` for all metrics.
- The `unit` column is available for future use.

## 📚 More

- See the published `config/metrics.php` for all options.
- See the migration for the table structure.
- For advanced usage, see the helper functions and artisan commands provided.

## Commands

### `metrics:install`

Installs the package by publishing configuration and migration files.

```bash
php artisan metrics:install
```

Options:

- `--force`: Overwrite existing files

### `metrics:flush`

Flushes cached metrics to the database.

```bash
php artisan metrics:flush
```

Options:

- `--force`: Force flush even if cache is disabled

### `metrics:clean`

Cleans old metrics from the database.

```bash
php artisan metrics:clean
```

Options:

- `--days=30`: Number of days to keep (default: 30)
- `--force`: Force the operation without confirmation

## Configuration

The package configuration is located in `config/metrics.php`. You can customize the following options:

- `enabled`: Enable/disable metrics recording
- `log_metrics`: Log metrics to Laravel's log system
- `default_tags`: Default tags to add to all metrics
- `cache`: Cache configuration (store, prefix, TTL, batch size, flush interval)
- `table`: Database table name for storing metrics
- `connection`: Database connection to use (null to disable database storage)

## Environment Variables

You can configure the package using these environment variables:

```env
METRICS_ENABLED=true
METRICS_LOG=false
METRICS_TABLE=metrics
METRICS_DB_CONNECTION=null

# Cache Configuration
METRICS_CACHE_STORE=redis
METRICS_CACHE_PREFIX=metrics:
METRICS_CACHE_TTL=3600 # must not be smaller than METRICS_CACHE_FLUSH_INTERVAL
METRICS_CACHE_BATCH_SIZE=1000
METRICS_CACHE_FLUSH_INTERVAL=300
```

## Cache System

The package includes a caching system that stores metrics in cache first, then periodically flushes them to the database. This provides:

- **Better Performance**: Metrics are stored in fast cache storage
- **Reduced Database Load**: Batch inserts instead of individual records
- **Data Aggregation**: Similar metrics are automatically aggregated
- **Fault Tolerance**: Metrics survive application restarts

### Cache Configuration

- `METRICS_CACHE_STORE`: Cache store to use (default: redis)
- `METRICS_CACHE_PREFIX`: Prefix for cache keys (default: metrics:)
- `METRICS_CACHE_TTL`: Time to live for cached metrics (default: 3600 seconds)
- `METRICS_CACHE_BATCH_SIZE`: Number of metrics to insert in one batch (default: 1000)
- `METRICS_CACHE_FLUSH_INTERVAL`: How often to flush metrics (default: 300 seconds)

## Scheduled Tasks

Add these to your `app/Console/Kernel.php` to automatically flush and clean metrics:

```php
protected function schedule(Schedule $schedule): void
{
    // Flush metrics every 5 minutes
    $schedule->command('metrics:flush')->everyFiveMinutes();

    // Clean old metrics daily at 2 AM (keep last 90 days)
    $schedule->command('metrics:clean --days=90 --force')->dailyAt('02:00');
}
```

## Testing

Run the test suite:

```bash
composer test
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Grafana Integration

The package is designed to work seamlessly with Grafana for creating beautiful dashboards and visualizations.

### Database as Data Source

Connect your metrics database to Grafana:

1. **Add Data Source** in Grafana:

   - **PostgreSQL**: Use the PostgreSQL data source
   - **MySQL**: Use the MySQL data source

2. **Connection Settings**:
   ```
   Host: your-database-host
   Database: your-metrics-database
   User: your-database-user
   Password: your-database-password
   ```

### Recommended Dashboard Panels

1. **Time Series**: Response times, memory usage, CPU usage
2. **Stat**: Current values, totals, averages
3. **Table**: Top endpoints, error rates, recent events
4. **Gauge**: Current system health, utilization

### Alerting

Set up Grafana alerts based on your metrics:

```sql
-- High response time alert
SELECT avg(value) as avg_response_time
FROM metrics
WHERE name = 'api.response_time'
  AND recorded_at >= now() - interval '5 minutes'
HAVING avg(value) > 1000;  -- Alert if > 1 second
```

For more Grafana query examples, see [Grafana Queries](docs/grafana-queries.md).
