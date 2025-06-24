# Exception Tracking

Track exceptions and errors in your application.

## Common Use Cases

- **Database Errors**: Track query exceptions
- **API Errors**: Monitor external service failures
- **Validation Errors**: Track form validation issues
- **Authentication Errors**: Monitor login failures

## Global Exception Handler
By adding the metric in the global exception handler you will be able to capture every exceptions.Exceptions from the ignore list in `config/lamet.php` will be ignored. 
```php
// In app/Exceptions/Handler.php
use Itsemon245\Lamet\Metrics;

public function register(): void
{
    $this->reportable(function (Throwable $e) {
        Metrics::exception($e, tags: [
            'user' => auth()->user() ? auth()->user()->email : null,
        ]);
    });

}
```

## Auto-added Tags

The exception tracking automatically adds:

- `exception_class`: Exception class name
- `message`: Exception message (truncated)
- `file`: File where exception occurred
- `line`: Line number where exception occurred
- `code`: Error code of the exception
- `trace`: Truncated Trace

You can remove any of them in the `config/lamet.php` if you don't want

## Using the Facade

```php
use Itsemon245\Lamet\Facades\Metrics;

// Record an exception with default name
Metrics::exception($exception, [
    'user_id' => auth()->id(),
    'endpoint' => request()->path(),
]);

// Record with custom metric name
Metrics::exception($exception, [
    'context' => 'background_job',
], 'job.exception');
```

## Using Helper Functions

```php
// Record an exception
metricsException($exception, [
    'user_id' => auth()->id(),
    'endpoint' => request()->path(),
]);

// Record with custom name
metricsException($exception, [
    'context' => 'background_job',
], 'job.exception');
```

## Common Use Cases

### API Exception Tracking

```php
// In API middleware or controller
try {
    $result = $this->processRequest();
} catch (Exception $e) {
    Metrics::exception($e, [
        'api_version' => 'v1',
        'endpoint' => request()->path(),
        'method' => request()->method(),
    ], 'api.exception');

    throw $e;
}
```

### Background Job Exception Tracking

```php
// In job classes
public function handle()
{
    try {
        $this->processJob();
    } catch (Exception $e) {
        Metrics::exception($e, [
            'job_class' => static::class,
            'queue' => $this->queue,
            'attempt' => $this->attempts(),
        ], 'job.exception');

        throw $e;
    }
}
```

### Database Exception Tracking

```php
// In database operations
try {
    $user = User::create($data);
} catch (QueryException $e) {
    Metrics::exception($e, [
        'table' => 'users',
        'operation' => 'create',
        'user_id' => auth()->id(),
    ], 'database.exception');

    throw $e;
}
```