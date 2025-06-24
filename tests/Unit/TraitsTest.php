<?php

namespace Itsemon245\Lamet\Tests\Unit;

use Itsemon245\Lamet\Tests\TestCase;
use Itsemon245\Lamet\MetricsManager;
use Illuminate\Support\Facades\Cache;

class TraitsTest extends TestCase
{
    public function test_cache_trait_functionality()
    {
        $manager = app(MetricsManager::class);
        
        // Test cache key building
        $reflection = new \ReflectionClass($manager);
        $method = $reflection->getMethod('buildCacheKey');
        $method->setAccessible(true);
        
        $key = $method->invoke($manager, 'test.metric', ['tag' => 'value'], 'counter', 'ms');
        
        $this->assertStringContainsString('lamet_test:', $key);
        $this->assertStringContainsString('test.metric', $key);
    }

    public function test_database_trait_functionality()
    {
        $manager = app(MetricsManager::class);
        
        // Test metrics storage
        $manager->increment('test.metric', 5);
        $count = $manager->flush();
        
        $this->assertGreaterThan(0, $count);
        
        // Test metrics retrieval
        $metrics = $manager->getMetrics(['name' => 'test.metric']);
        $this->assertIsArray($metrics);
    }

    public function test_logging_trait_functionality()
    {
        config(['lamet.log_metrics' => true]);
        
        $manager = app(MetricsManager::class);
        
        // Should not throw error when logging is enabled
        $manager->record('test.metric', 42.5);
        
        $this->assertTrue(true);
    }
} 