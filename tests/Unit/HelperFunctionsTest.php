<?php

namespace Itsemon245\Lamet\Tests\Unit;

use Itsemon245\Lamet\Tests\TestCase;

class HelperFunctionsTest extends TestCase
{
    public function test_metrics_helper_function()
    {
        // Test recording a metric
        metrics('test.metric', 42.5, ['tag' => 'value']);
        
        // Test incrementing (default value)
        metrics('test.counter', tags: ['tag' => 'value']);
        
        $this->assertTrue(true); // Functions executed without error
    }

    public function test_metrics_time_helper_function()
    {
        $result = metrics_time('test.timer', function () {
            usleep(1000); // 1ms delay
            return 'success';
        }, ['tag' => 'value']);
        
        $this->assertEquals('success', $result);
    }

    public function test_metrics_increment_helper_function()
    {
        metrics_increment('test.counter', 5, ['tag' => 'value']);
        
        $this->assertTrue(true); // Function executed without error
    }

    public function test_metrics_decrement_helper_function()
    {
        metrics_decrement('test.counter', 3, ['tag' => 'value']);
        
        $this->assertTrue(true); // Function executed without error
    }

    public function test_metrics_flush_helper_function()
    {
        $count = metrics_flush();
        
        $this->assertIsInt($count);
    }

    public function test_metrics_get_helper_function()
    {
        $metrics = metrics_get(['name' => 'test.metric']);
        
        $this->assertIsArray($metrics);
    }

    public function test_metrics_clean_helper_function()
    {
        $deleted = metrics_clean(30);
        
        $this->assertIsInt($deleted);
    }
} 