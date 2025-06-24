# Advanced Metrics Usage Examples

## 1. Gauge Metric (e.g., Memory Usage)

```php
Metrics::record('memory.usage', 1048576, [
    'host' => gethostname(),
], 'gauge', 'bytes');
```

## 2. Timer/Duration Metric (e.g., API Response Time)

```php
Metrics::record('api.response_time', 120, [
    'endpoint' => '/api/users',
    'method' => 'GET',
], 'timer', 'ms');
```

## 3. Percentage Metric (e.g., CPU Usage)

```php
Metrics::record('cpu.usage', 0.85, [
    'host' => gethostname(),
], 'gauge', '%');
```

## 4. Business Metric (e.g., Revenue)

```php
Metrics::record('revenue.daily', 199.99, [
    'currency' => 'USD',
    'date' => now()->toDateString(),
], 'gauge', 'usd');
```

## 5. Custom Tagging for Multi-dimensional Analysis

```php
Metrics::increment('feature.usage', 1, [
    'feature' => 'dark_mode',
    'user_id' => 123,
    'plan' => 'pro',
], 'counter');
```

## 6. Exception Metric with Extra Context

```php
Metrics::increment('exception.occurrence', 1, [
    'exception_class' => get_class($exception),
    'file' => $exception->getFile(),
    'line' => $exception->getLine(),
    'user_id' => optional(auth()->user())->id,
    'endpoint' => request()->path(),
    'method' => request()->method(),
    'custom_context' => 'background_job',
], 'counter');
```

## 7. Using Helper Functions for Advanced Metrics

```php
metrics('disk.free', 500, [
    'host' => gethostname(),
], 'gauge', 'GB');

metrics_increment('user.signup', 1, [
    'referrer' => 'newsletter',
], 'counter');
```
