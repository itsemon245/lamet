# Data Retrieval

## Using the Facade

```php
use Itsemon245\Lamet\Facades\Metrics;

// Get all metrics
$metrics = Metrics::getMetrics();

// Get metrics with filters
$metrics = Metrics::getMetrics([
    'name' => 'api.requests',
    'from' => '2024-01-01',
    'to' => '2024-01-31',
]);
```

## Using Helper Functions

```php
// Get all metrics
$metrics = metricsGet();

// Get metrics with filters
$metrics = metricsGet([
    'name' => 'api.requests',
    'from' => '2024-01-01',
    'to' => '2024-01-31',
]);
```

## Filter Options

### Basic Filters

```php
// Filter by metric name
$metrics = Metrics::getMetrics(['name' => 'user.login']);

// Filter by metric type
$metrics = Metrics::getMetrics(['type' => 'counter']);

// Filter by date range
$metrics = Metrics::getMetrics([
    'from' => '2024-01-01',
    'to' => '2024-01-31',
]);
```

### Combined Filters

```php
// Multiple filters combined
$metrics = Metrics::getMetrics([
    'name' => 'api.requests',
    'type' => 'counter',
    'from' => '2024-01-01',
    'to' => '2024-01-31',
]);
```

## Common Query Patterns

### Recent Metrics

```php
// Get metrics from last 24 hours
$metrics = Metrics::getMetrics([
    'from' => now()->subDay()->toDateString(),
    'to' => now()->toDateString(),
]);
```

### Error Metrics

```php
// Get exception metrics
$exceptions = Metrics::getMetrics([
    'name' => 'exception.occurrence',
    'from' => now()->subWeek()->toDateString(),
]);
```

### Performance Metrics

```php
// Get slow database queries
$slowQueries = Metrics::getMetrics([
    'name' => 'db.query.slow',
    'from' => now()->subDay()->toDateString(),
]);
```

### Type-Specific Metrics

```php
// Get all timer metrics
$timerMetrics = Metrics::getMetrics([
    'type' => 'timer',
    'from' => now()->subDay()->toDateString(),
]);
```

## Data Analysis

### Aggregation Examples

```php
// Get total requests per endpoint
$endpointStats = collect(Metrics::getMetrics([
    'name' => 'api.requests',
    'from' => now()->subDay()->toDateString(),
]))->groupBy('tags.endpoint')
  ->map(function ($group) {
      return $group->sum('value');
  });
```

### Time Series Analysis

```php
// Get hourly metrics
$hourlyMetrics = collect(Metrics::getMetrics([
    'name' => 'api.response_time',
    'from' => now()->subDay()->toDateString(),
]))->groupBy(function ($metric) {
    return Carbon::parse($metric['recorded_at'])->format('Y-m-d H:00:00');
});
```

## Export and Reporting

### CSV Export

```php
// Export metrics to CSV
$metrics = Metrics::getMetrics([
    'name' => 'user.actions',
    'from' => '2024-01-01',
    'to' => '2024-01-31',
]);

$csv = collect($metrics)->map(function ($metric) {
    return [
        'name' => $metric['name'],
        'value' => $metric['value'],
        'recorded_at' => $metric['recorded_at'],
        'tags' => json_encode($metric['tags']),
    ];
})->toArray();
```

### JSON API Response

```php
// Return metrics as API response
public function getMetrics(Request $request)
{
    $filters = $request->only(['name', 'type', 'from', 'to']);
    $metrics = Metrics::getMetrics($filters);

    return response()->json([
        'data' => $metrics,
        'count' => count($metrics),
    ]);
}
```

## Performance Considerations

### Caching Results

```php
// Cache frequently accessed metrics
$metrics = Cache::remember('metrics:api:requests', 300, function () {
    return Metrics::getMetrics([
        'name' => 'api.requests',
        'from' => now()->subDay()->toDateString(),
    ]);
});
```

### Large Dataset Handling

```php
// For large datasets, consider processing in chunks
$allMetrics = [];
$offset = 0;
$limit = 1000;

do {
    $metrics = Metrics::getMetrics([
        'from' => now()->subDays(30)->toDateString(),
        'to' => now()->toDateString(),
    ]);

    $allMetrics = array_merge($allMetrics, $metrics);
    $offset += $limit;

} while (count($metrics) === $limit);
```

## Available Filter Fields

The following filters are supported:

- **`name`**: Filter by metric name (exact match)
- **`type`**: Filter by metric type (e.g., 'counter', 'timer', 'gauge')
- **`from`**: Filter metrics recorded from this date (inclusive)
- **`to`**: Filter metrics recorded up to this date (inclusive)

## Notes

- Date filters use the `recorded_at` column
- Results are ordered by `recorded_at` in descending order (newest first)
- All filters are optional - omitting filters returns all metrics
- Date formats should match your database's expected format (typically 'Y-m-d' or 'Y-m-d H:i:s')
