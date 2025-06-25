<?php

namespace Itsemon245\Lamet\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed record(string $name, float $value, array $tags = [], ?string $type = null, ?string $unit = null)
 * @method static mixed increment(string $name, int $value = 1, array $tags = [], ?string $type = null, ?string $unit = null)
 * @method static mixed decrement(string $name, int $value = 1, array $tags = [], ?string $type = null, ?string $unit = null)
 * @method static mixed time(string $name, callable $callback, array $tags = [], ?string $type = null, ?string $unit = 'ms')
 * @method static mixed dbQuery(\Illuminate\Database\Events\QueryExecuted $event, array $additionalTags = [], ?string $name = null)
 * @method static mixed exception(\Throwable $throwable, array $additionalTags = [], ?string $name = null)
 * @method static int flush()
 * @method static array getMetrics(array $filters = [])
 * @method static int|string clean(int $daysToKeep = 30, bool $dryRun = false)
 * @method static array getConfig()
 *
 * @see \Itsemon245\Lamet\MetricsManager
 */
class Metrics extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'metrics';
    }
}
