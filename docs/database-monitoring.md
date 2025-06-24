# Database Query Monitoring

## Using the Facade

```php
use Itsemon245\Lamet\Facades\Metrics;
use Illuminate\Database\Events\QueryExecuted;

// Record a database query event
Metrics::dbQuery($queryEvent, [
    'custom_tag' => 'value',
]);
```

## Using Helper Functions

```php
use Illuminate\Database\Events\QueryExecuted;

// Record a database query event
metricsDbQuery($queryEvent, [
    'custom_tag' => 'value',
]);
```

## Configuration

Database query monitoring is configured in `config/lamet.php`:

```php
'db_query' => [
    'enabled' => true,
    'name' => 'db.query',
    'slow_query_threshold' => 1500, // milliseconds
    'separate_metric_for_slow_queries' => true,
    'tags' => ['sql', 'duration', 'file', 'line'],
],
```

## Automatic Monitoring

The package automatically monitors database queries when enabled. You can also manually record query events:

```php
// In a service provider or middleware
Event::listen(QueryExecuted::class, function (QueryExecuted $event) {
    Metrics::dbQuery($event, [
        'context' => 'manual_tracking',
    ]);
});
```

## Query Tags

The following tags are automatically added to database query metrics:

- `sql`: The SQL query (truncated to 200 characters)
- `duration`: Query execution time in milliseconds
- `file`: The file where the query was executed
- `line`: The line number where the query was executed

## Slow Query Detection

Queries exceeding the `slow_query_threshold` are automatically tagged and can be recorded as separate metrics:

```php
// Configuration for slow query handling
'db_query' => [
    'slow_query_threshold' => 1000, // 1 second
    'separate_metric_for_slow_queries' => true,
],
```

## Use Cases

### Performance Monitoring

```php
// Monitor specific database operations
Event::listen(QueryExecuted::class, function (QueryExecuted $event) {
    if (str_contains($event->sql, 'users')) {
        Metrics::dbQuery($event, [
            'table' => 'users',
            'operation' => 'user_related_query',
        ]);
    }
});
```

### Query Analysis

```php
// Track queries by table
Event::listen(QueryExecuted::class, function (QueryExecuted $event) {
    $table = extractTableFromSql($event->sql);
    Metrics::dbQuery($event, [
        'table' => $table,
        'query_type' => getQueryType($event->sql),
    ]);
});
```

### Application Context

```php
// Add application context to queries
Event::listen(QueryExecuted::class, function (QueryExecuted $event) {
    Metrics::dbQuery($event, [
        'user_id' => optional(auth()->user())->id,
        'endpoint' => request()->path(),
        'request_id' => request()->id(),
    ]);
});
```
