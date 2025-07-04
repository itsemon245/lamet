![Laravel Lamet Banner](https://placehold.co/1200x400/667eea/white?text=LAMET&font=Raleway)

<p align="center">
 <a href="https://packagist.org/packages/itsemon245/lamet"><img src="https://img.shields.io/packagist/dt/itsemon245/lamet?style=for-the-badge&label=Downloads&color=61C9A8" alt="Total Downloads"></a>
 <a href="https://packagist.org/packages/itsemon245/lamet"><img src="https://img.shields.io/packagist/v/itsemon245/lamet?style=for-the-badge&label=Version" alt="Latest Stable Version"></a>
 <a href="https://packagist.org/packages/itsemon245/lamet"><img src="https://img.shields.io/packagist/l/itsemon245/lamet?style=for-the-badge&label=License" alt="License"></a>
</p>

# Laravel Lamet (Laravel + Metrics)

A simple, high-performance package to record and aggregate metrics in Laravel applications, ready for Grafana dashboards.

## 📋 Table of Contents

- [🚀 Installation](#-installation)
- [✨ Usage](#-usage)
  - [Basic Usage](#basic-usage)
  - [Available Methods](#available-methods)
- [📝 Notes](#-notes)
- [📚 More](#-more)
- [Commands](#commands)
  - [lamet:install](#lametinstall)
  - [lamet:flush](#lametflush)
  - [lamet:clean](#lametclean)
- [Configuration](#configuration)
- [Environment Variables](#environment-variables)
- [Cache System](#cache-system)
  - [Cache Configuration](#cache-configuration-1)
- [Scheduled Tasks](#scheduled-tasks)
- [Grafana Integration](#grafana-integration)
  - [Database as Data Source](#database-as-data-source)
  - [Recommended Dashboard Panels](#recommended-dashboard-panels)
  - [Alerting](#alerting)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

## 🚀 Installation

1. **Install via Composer:**

   ```bash
   composer require itsemon245/lamet
   ```

2. **Publish the configuration and migration:**

   ```bash
   php artisan lamet:install
   ```

   This will publish `config/lamet.php` and the migration to `database/migrations/lamet/`.

   Alternatively, you can publish them separately:

   ```bash
   php artisan vendor:publish --tag=lamet-config
   php artisan vendor:publish --tag=lamet-migrations
   ```

3. **Configure your database and cache:**

   - Set up your desired database connection in `config/database.php` (e.g., `sqlite`, `mysql`, `pgsql`). _Postgres is recommended_

   ```php
    'lamet' => [
         'driver' => env('LAMET_DB_CONNECTION', 'pgsql'),
         'url' => env('LAMET_DATABASE_URL'),
         'host' => env('LAMET_DB_HOST', '127.0.0.1'),
         'port' => env('LAMET_DB_PORT', '5432'),
         'database' => env('LAMET_DB_DATABASE', 'lamet_metrics'),
         'username' => env('LAMET_DB_USERNAME', 'root'),
         'password' => env('LAMET_DB_PASSWORD', ''),
         'charset' => 'utf8',
         'prefix' => '',
         'prefix_indexes' => true,
         'search_path' => env('LAMET_DB_CONNECTION', 'pgsql') === 'pgsql' ? 'public' : null,
         'sslmode' => env('LAMET_DB_CONNECTION', 'pgsql') === 'pgsql' ? 'prefer' : null,
     ],
   ```

   - Create a new cache store or use an existing one from `config/cache.php` (e.g., `redis`).
   - Add and update the .env variables for the new database connection

   ```bash
   #Database Connection
   LAMET_DB_CONNECTION=pgsql
   LAMET_DB_HOST=127.0.0.1
   LAMET_DB_PORT=5432
   LAMET_DB_DATABASE=lamet_metrics
   LAMET_DB_USERNAME=root
   LAMET_DB_PASSWORD=password

   #Other Config options (these are the defaults)
   LAMET_CACHE_STORE=redis
   LAMET_CACHE_PREFIX=metrics:
   LAMET_CACHE_TTL=3600
   LAMET_CACHE_BATCH_SIZE=1000
   LAMET_LOG=false
   ```

   > [!NOTE] > **Quick Setup**: Use the provided `misc/postgres-docker-compose.yml` example to quickly spin up a PostgreSQL container for development and testing.

4. **Run the migration:**

   ```bash
   php artisan migrate
   ```

   > [!IMPORTANT]
   > The 5th step is very important. If you skip this your metrics won't be saved in the database.
   > Also keep in mind that you have to set the frequency lower than the ttl value in `config/lamet.php`

5. **Schedule periodic flushing:**
   In `app/Console/Kernel.php`:
   ```php
   protected function schedule(Schedule $schedule): void
   {
       $schedule->command('lamet:flush')->everyFiveMinutes();
   }
   ```
   > [!TIP]
   > Higher frequency means less granularity, not suitable for realtime metrics but lower memory usage
   > Lower frequency means more granularity, suitable for realtime metrics but higher memory usage
   > Keep it between 5-20 minutes in general.

## ✨ Usage

### Basic Usage

Record a simple metric using the facade:

```php
use Itsemon245\Lamet\Facades\Metrics;

// Record an API request
Metrics::increment('api.requests', 1, [
    'endpoint' => '/users',
    'method' => 'GET',
    'status_code' => 200
]);
```

### Available Methods

- **Basic Metrics**: [Counter & Gauge Metrics](docs/basic-metrics.md)
- **Timing**: [Time-based Metrics](docs/timing-metrics.md)
- **Exceptions**: [Exception Tracking](docs/exception-tracking.md)
- **Database**: [Database Query Monitoring](docs/database-monitoring.md)
- **Cache Management**: [Cache Operations](docs/cache-management.md)
- **Data Retrieval**: [Data Retrieval](docs/data-retrieval.md)
- **Cleanup**: [Cleanup Operations](docs/cleanup-operations.md)

Each method supports three usage patterns:

- **Facade**: `Metrics::methodName()`
- **Helper Functions**: `metricsMethodName()`
- **Dependency Injection**: Inject `MetricsManager`

---

For advanced usage and query examples, see:

- [Query Examples](docs/query-examples.md) - Common SQL queries for Grafana
- [Advanced Usage](docs/advance-usage.md) - Advanced metric types and patterns
- [Grafana Queries](docs/grafana-queries.md) - Grafana-specific query examples

## 📝 Notes

- The `tags` column is flexible and can store any key-value pairs.
- The `recorded_at` column is used for time-series queries in Grafana.
- The `type` column defaults to `counter` for all metrics.
- The `unit` column is available for any unit

## 📚 More

- See the published `config/lamet.php` for all options.
- See the migration for the table structure.
- For advanced usage, see the helper functions and artisan commands provided.

## Commands

### `lamet:install`

Installs the package by publishing configuration and migration files.

```bash
php artisan lamet:install
```

Options:

- `--force`: Overwrite existing files

### `lamet:flush`

Flushes cached metrics to the database.

```bash
php artisan lamet:flush
```

Options:

- `--dry-run`: Run the command without actually doing anything
- `-P|--print`: Print the flush keys
- `--force`: Force flush even if cache is disabled

### `lamet:clean`

Cleans old metrics from the database.

```bash
php artisan lamet:clean
```

Options:

- `--dry-run`: Run the command without actually doing anything
- `--days=30`: Number of days to keep (default: 30)
- `--force`: Force the operation without confirmation

## Configuration

The package configuration is located in `config/lamet.php`. You can customize the following options:

### General Settings

- `enabled`: Enable/disable metrics recording
- `log_metrics`: Log metrics to Laravel's log system
- `default_tags`: Default tags to add to all metrics (environment, app_name, user fields)

### Database Query Monitoring

- `db_query.enabled`: Enable/disable auto database query monitoring
- `db_query.metric_name`: Name for database query metrics
- `db_query.tags`: Tags to include with database queries
- `db_query.store_only_slow_query`: Store only slow queries (default: true)
- `db_query.slow_query_name_suffix`: Suffix for slow query metric names (default: '.slow')
- `db_query.slow_query_threshold`: Threshold in milliseconds for slow queries (default: 1500ms)

### Exception Monitoring

- `exception.enabled`: Enable/disable auto exception monitoring
- `exception.metric_name`: Name for exception metrics
- `exception.tags`: Tags to include with exception metrics

### Ignore Configuration

- `ignore.paths`: Array of paths to ignore when recording metrics
- `ignore.exceptions`: Array of exception classes to ignore
- `ignore.db_query.tables`: Array of database tables to ignore when monitoring queries
- `ignore.db_query.sql_patterns`: Array of regex patterns to ignore specific SQL queries

### Cache Configuration

- `cache.store`: Cache store to use (default: redis)
- `cache.prefix`: Prefix for cache keys (default: metrics:)
- `cache.ttl`: Time to live for cached metrics (default: 3600 seconds)
- `cache.batch_size`: Number of metrics to insert in one batch (default: 1000)

### Database Settings

- `table`: Database table name for storing metrics
- `connection`: Database connection to use (null to disable database storage)

## Environment Variables

You can configure the package using these environment variables:

```env
LAMET_ENABLED=true
LAMET_LOG=false
LAMET_TABLE=lamet

# Cache Configuration
LAMET_CACHE_STORE=redis
LAMET_CACHE_PREFIX=lamet:
LAMET_CACHE_TTL=3600
LAMET_CACHE_BATCH_SIZE=1000
```

## Cache System

The package includes a caching system that stores metrics in cache first, then periodically flushes them to the database. This provides:

- **Better Performance**: Metrics are stored in fast cache storage
- **Reduced Database Load**: Batch inserts instead of individual records
- **Data Aggregation**: Similar metrics are automatically aggregated
- **Fault Tolerance**: Metrics survive application restarts

### Cache Configuration

- `LAMET_CACHE_STORE`: Cache store to use (default: redis)
- `LAMET_CACHE_PREFIX`: Prefix for cache keys (default: metrics:)
- `LAMET_CACHE_TTL`: Time to live for cached metrics (default: 3600 seconds)
- `LAMET_CACHE_BATCH_SIZE`: Number of metrics to insert in one batch (default: 1000)

## Scheduled Tasks

Add these to your `app/Console/Kernel.php` to automatically flush and clean metrics:

```php
protected function schedule(Schedule $schedule): void
{
    // Flush metrics every 5 minutes
    $schedule->command('lamet:flush')->everyFiveMinutes();

    // Clean old metrics daily at 2 AM (keep last 90 days)
    // $schedule->command('lamet:clean --days=90 --force')->dailyAt('02:00');
}
```

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
FROM lamet
WHERE name = 'api.response_time'
  AND recorded_at >= now() - interval '5 minutes'
HAVING avg(value) > 1000;  -- Alert if > 1 second
```

For more Grafana query examples, see [Grafana Queries](docs/grafana-queries.md).

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
