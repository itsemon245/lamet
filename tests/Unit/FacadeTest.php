<?php

namespace Itsemon245\Lamet\Tests\Unit;

use Itsemon245\Lamet\Tests\TestCase;
use Itsemon245\Lamet\Facades\Metrics;

class FacadeTest extends TestCase
{
    public function test_facade_can_record_metrics()
    {
        Metrics::record('test.metric', 42.5, ['tag' => 'value']);
        
        $this->assertTrue(true); // Metric recorded without error
    }

    public function test_facade_can_increment_metrics()
    {
        Metrics::increment('test.counter', 5, ['tag' => 'value']);
        
        $this->assertTrue(true); // Metric incremented without error
    }

    public function test_facade_can_decrement_metrics()
    {
        Metrics::decrement('test.counter', 3, ['tag' => 'value']);
        
        $this->assertTrue(true); // Metric decremented without error
    }

    public function test_facade_can_time_function_execution()
    {
        $result = Metrics::time('test.timer', function () {
            usleep(1000); // 1ms delay
            return 'success';
        }, ['tag' => 'value']);
        
        $this->assertEquals('success', $result);
    }

    public function test_facade_returns_configuration()
    {
        $config = Metrics::getConfig();
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('enabled', $config);
    }
} 