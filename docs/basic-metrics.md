# Basic Metrics

Record counter and gauge metrics using increment, decrement, and record methods.

## Facade Usage

```php
use Itsemon245\Lamet\Facades\Metrics;

// Increment a counter
Metrics::increment('api.requests', 1, [
    'endpoint' => '/users',
    'method' => 'GET'
]);

// Decrement a counter
Metrics::decrement('active.users', 1, ['user_id' => 123]);

// Record a gauge value
Metrics::record('memory.usage', 512.5, ['unit' => 'MB']);
```

## Helper Functions

```php
// Increment a counter
metricsIncrement('api.requests', 1, ['endpoint' => '/users']);

// Decrement a counter
metricsDecrement('active.users', 1, ['user_id' => 123]);

// Record a gauge value
metrics('memory.usage', 512.5, ['unit' => 'MB']);
```

## Dependency Injection

```php
use Itsemon245\Lamet\MetricsManager;

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

## Common Use Cases

- **API Requests**: Track request counts by endpoint and method
- **User Activity**: Monitor active users, logins, registrations
- **System Metrics**: Memory usage, CPU load, disk space
- **Business Metrics**: Orders, revenue, conversions
