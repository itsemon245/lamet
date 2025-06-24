<?php

namespace Itsemon245\Lamet\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void record(string $name, float $value, array $tags = [], ?string $type = null, ?string $unit = null)
 * @method static void increment(string $name, int $value = 1, array $tags = [], ?string $type = null, ?string $unit = null)
 * @method static void decrement(string $name, int $value = 1, array $tags = [], ?string $type = null, ?string $unit = null)
 * @method static mixed time(string $name, callable $callback, array $tags = [], ?string $type = null, ?string $unit = 'ms')
 * @method static int flush()
 * @method static array getMetrics(array $filters = [])
 * @method static int clean(int $daysToKeep = 30)
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
        return 'lamet';
    }
}
