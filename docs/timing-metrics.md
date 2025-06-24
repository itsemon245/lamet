# Time-based Metrics

Measure execution time of functions and operations.

## Facade Usage

```php
use Itsemon245\Lamet\Facades\Metrics;

// Time a function execution
$result = Metrics::time('database.query', function () {
    return User::where('active', true)->get();
}, ['table' => 'users']);

// Time an API call
$response = Metrics::time('external.api.call', function () {
    return Http::get('https://api.example.com/data');
}, ['endpoint' => '/data']);
```

## Helper Functions

```php
// Time a function execution
$result = metricsTime('database.query', function () {
    return User::where('active', true)->get();
}, ['table' => 'users']);

// Time an API call
$response = metricsTime('external.api.call', function () {
    return Http::get('https://api.example.com/data');
}, ['endpoint' => '/data']);
```

## Dependency Injection

```php
use Itsemon245\Lamet\MetricsManager;

class UserService
{
    public function __construct(private MetricsManager $metrics)
    {
    }

    public function getActiveUsers()
    {
        return $this->metrics->time('user.service.active_users', function () {
            return User::where('active', true)->get();
        }, ['service' => 'user']);
    }
}
```

## Common Use Cases

- **Database Queries**: Measure query performance
- **External API Calls**: Monitor third-party service response times
- **File Operations**: Track file read/write performance
- **Complex Calculations**: Time expensive operations
