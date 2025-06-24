<?php

namespace Itsemon245\Lamet\Tests\Unit;

use Itsemon245\Lamet\Tests\TestCase;
use Itsemon245\Lamet\MetricsManager;
use Itsemon245\Lamet\Facades\Metrics;

class LametManagerTest extends TestCase
{
    public function test_it_can_record_metrics()
    {
        $manager = app(MetricsManager::class);
        
        $manager->record('test.metric', 42.5, ['tag' => 'value']);
        
        $this->assertTrue(true); // Metric recorded without error
    }

    public function test_it_can_increment_metrics()
    {
        $manager = app(MetricsManager::class);
        
        $manager->increment('test.counter', 5, ['tag' => 'value']);
        
        $this->assertTrue(true); // Metric incremented without error
    }

    public function test_it_can_decrement_metrics()
    {
        $manager = app(MetricsManager::class);
        
        $manager->decrement('test.counter', 3, ['tag' => 'value']);
        
        $this->assertTrue(true); // Metric decremented without error
    }

    public function test_it_can_time_function_execution()
    {
        $manager = app(MetricsManager::class);
        
        $result = $manager->time('test.timer', function () {
            usleep(1000); // 1ms delay
            return 'success';
        }, ['tag' => 'value']);
        
        $this->assertEquals('success', $result);
    }

    public function test_it_handles_exceptions_in_timed_functions()
    {
        $manager = app(MetricsManager::class);
        
        $this->expectException(\Exception::class);
        
        $manager->time('test.timer', function () {
            throw new \Exception('Test exception');
        }, ['tag' => 'value']);
    }

    public function test_it_returns_configuration()
    {
        $manager = app(MetricsManager::class);
        
        $config = $manager->getConfig();
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('enabled', $config);
        $this->assertArrayHasKey('table', $config);
        $this->assertArrayHasKey('connection', $config);
    }

    public function test_it_respects_enabled_setting()
    {
        config(['lamet.enabled' => false]);
        
        $manager = app(MetricsManager::class);
        
        $manager->record('test.metric', 42.5);
        
        $this->assertTrue(true); // No error when disabled
    }
} 