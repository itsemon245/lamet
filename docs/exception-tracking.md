# Exception Tracking

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

### Global Exception Handler

```php
// In app/Exceptions/Handler.php
public function register(): void
{
    $this->reportable(function (Throwable $e) {
        Metrics::exception($e, [
            'user_id' => optional(auth()->user())->id,
            'endpoint' => request()->path(),
            'method' => request()->method(),
            'ip' => request()->ip(),
        ]);
    });
}
```

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

## Exception Tags

The exception tracking automatically adds these tags:

- `exception_class`: The class name of the exception
- `exception_message`: The exception message (truncated to 200 characters)
- `exception_file`: The file where the exception occurred
- `exception_line`: The line number where the exception occurred

You can add additional tags for better categorization and analysis.
