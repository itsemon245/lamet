# Laravel Metrics

A simple package to record metrics in production at scale for Laravel applications.

## Installation

1. Install the package via Composer:

```bash
composer require itsemon245/laravel-metrics
```

2. Run the installation command:

```bash
php artisan metrics:install
```

This command will:

- Publish the configuration file to `config/metrics.php`
- Publish migrations to `database/migrations/metrics/`
- Display setup instructions

3. Configure your database connection in `config/database.php`:

```php
'sqlite' => [
    'driver' => 'sqlite',
    'database' => storage_path('logs/metrics.sqlite'),
    'prefix' => '',
],
```

4. Set the database connection in your `.env` file:

```env
METRICS_DB_CONNECTION=sqlite
```

5. Run the migrations:

```bash
php artisan migrate
```

**Note**: The metrics migrations will automatically use the configured database connection (`METRICS_DB_CONNECTION`) and will be included in the standard Laravel migration process.

### Creating Additional Metrics Migrations

To create additional migrations for your metrics package, extend the `MetricsMigration` base class:

```php
<?php

use Itsemon245\Metrics\Database\Migrations\MetricsMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends MetricsMigration
{
    public function up(): void
    {
        $schema = $this->getMetricsSchema();

        $schema->create('your_new_table', function (Blueprint $table) {
            // Your table definition
        });
    }

    public function down(): void
    {
        $schema = $this->getMetricsSchema();
        $schema->dropIfExists('your_new_table');
    }
};
```

This ensures all metrics migrations use the same database connection automatically.

### Database-Specific Features

The package automatically adapts to different database drivers:

- **PostgreSQL**: Uses `jsonb` columns with GIN indexes for better JSON query performance
- **MySQL/SQLite**: Uses `json` columns with standard indexing
- **Automatic Detection**: The migration system detects your database driver and applies appropriate optimizations

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
- `driver`: The driver to use for sending metrics

## Usage

### Using the Facade

```php
use Itsemon245\Metrics\Facades\Metrics;

// Record a metric
Metrics::record('user.login', 1, ['user_id' => 123]);

// Increment a counter
Metrics::increment('api.requests', 1, ['endpoint' => '/users']);

// Decrement a counter
Metrics::decrement('active.users', 1);

// Time a function execution
$result = Metrics::time('database.query', function () {
    return User::where('active', true)->get();
}, ['table' => 'users']);
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

## Environment Variables

You can configure the package using these environment variables:

```env
METRICS_ENABLED=true
METRICS_LOG=false
METRICS_DRIVER=database
METRICS_DB_CONNECTION=sqlite
METRICS_LOG_CHANNEL=daily

# Cache Configuration
METRICS_CACHE_ENABLED=true
METRICS_CACHE_STORE=redis
METRICS_CACHE_PREFIX=metrics:
METRICS_CACHE_TTL=3600
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

- `METRICS_CACHE_ENABLED`: Enable/disable caching (default: true)
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

    // Clean old metrics daily at 2 AM
    $schedule->command('metrics:clean --days=30 --force')->dailyAt('02:00');
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
   - **SQLite**: Use the SQLite data source (via SQLite plugin)

2. **Connection Settings**:
   ```
   Host: your-database-host
   Database: your-metrics-database
   User: your-database-user
   Password: your-database-password
   ```

### Example Grafana Queries

#### Time Series Graph

```sql
-- Response time over time
SELECT
    recorded_at as time,
    value,
    name as metric
FROM metrics
WHERE name = 'api.response_time'
  AND recorded_at >= $__timeFrom()
  AND recorded_at <= $__timeTo()
ORDER BY recorded_at;
```

#### Counter Graph

```sql
-- Request count over time
SELECT
    recorded_at as time,
    value,
    name as metric
FROM metrics
WHERE name = 'api.requests'
  AND recorded_at >= $__timeFrom()
  AND recorded_at <= $__timeTo()
ORDER BY recorded_at;
```

#### Tag-based Filtering

```sql
-- Filter by tags (PostgreSQL jsonb)
SELECT
    recorded_at as time,
    value,
    tags->>'endpoint' as endpoint
FROM metrics
WHERE name = 'api.response_time'
  AND tags @> '{"endpoint": "/users"}'
  AND recorded_at >= $__timeFrom()
  AND recorded_at <= $__timeTo()
ORDER BY recorded_at;
```

#### Multi-metric Dashboard

```sql
-- Multiple metrics in one query
SELECT
    recorded_at as time,
    value,
    name as metric
FROM metrics
WHERE name IN ('api.response_time', 'api.requests', 'memory.usage')
  AND recorded_at >= $__timeFrom()
  AND recorded_at <= $__timeTo()
ORDER BY recorded_at, name;
```

### Dashboard Variables

Create dynamic dashboards with variables:

#### Metric Names Variable

```sql
SELECT DISTINCT name FROM metrics ORDER BY name;
```

#### Tag Values Variable

```sql
-- For PostgreSQL
SELECT DISTINCT jsonb_object_keys(tags) as tag_key FROM metrics;

-- For MySQL/SQLite
SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(tags, '$.endpoint')) as endpoint
FROM metrics
WHERE JSON_EXTRACT(tags, '$.endpoint') IS NOT NULL;
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

## Scheduled Tasks
