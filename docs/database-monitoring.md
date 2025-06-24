# Database Query Monitoring

> [!NOTE]
> If the `db_query.enabled` option in `config/lamet.php` is set to `true`, database queries will be automatically monitored. You don't need to manually call these methods unless you want custom query tracking.

## Usage:
```php
// In a service provider or middleware
Event::listen(QueryExecuted::class, function (QueryExecuted $event) {
    //Facade version
    Metrics::dbQuery($event, [
        'context' => 'manual_tracking',
    ]);

    //Helper version
    metricsDbQuery($event, [
        'context' => 'manual_tracking',
    ]);
});
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
