<?php

namespace Itsemon245\Lamet\Tests\Integration;

use Itsemon245\Lamet\Tests\TestCase;
use Itsemon245\Lamet\Facades\Metrics;
use Illuminate\Support\Facades\DB;

class CacheAndDatabaseTest extends TestCase
{
    public function test_metrics_are_cached_and_flushed_to_database()
    {
        // Record some metrics
        Metrics::increment('test.counter', 5, ['tag' => 'value1']);
        Metrics::increment('test.counter', 3, ['tag' => 'value2']);
        Metrics::record('test.gauge', 42.5, ['tag' => 'value3']);
        
        // Flush to database
        $count = Metrics::flush();
        
        $this->assertGreaterThan(0, $count);
        
        // Check if metrics are in database
        $metrics = DB::connection('testing')->table('lamet')->get();
        $this->assertGreaterThan(0, $metrics->count());
        
        // Verify metric data
        $counterMetric = $metrics->where('name', 'test.counter')->first();
        $this->assertNotNull($counterMetric);
        $this->assertEquals(8, $counterMetric->value); // 5 + 3
        
        $gaugeMetric = $metrics->where('name', 'test.gauge')->first();
        $this->assertNotNull($gaugeMetric);
        $this->assertEquals(42.5, $gaugeMetric->value);
    }

    public function test_metrics_aggregation_in_cache()
    {
        // Record same metric multiple times
        Metrics::increment('test.counter', 1, ['tag' => 'same']);
        Metrics::increment('test.counter', 1, ['tag' => 'same']);
        Metrics::increment('test.counter', 1, ['tag' => 'same']);
        
        // Flush to database
        $count = Metrics::flush();
        
        $this->assertEquals(1, $count); // Should be aggregated to 1 metric
        
        // Check aggregated value
        $metric = DB::connection('testing')->table('lamet')
            ->where('name', 'test.counter')
            ->first();
        
        $this->assertNotNull($metric);
        $this->assertEquals(3, $metric->value); // 1 + 1 + 1 = 3
    }

    public function test_metrics_with_different_tags_are_separate()
    {
        // Record same metric with different tags
        Metrics::increment('test.counter', 1, ['tag' => 'value1']);
        Metrics::increment('test.counter', 1, ['tag' => 'value2']);
        
        // Flush to database
        $count = Metrics::flush();
        
        $this->assertEquals(2, $count); // Should be 2 separate metrics
        
        // Check both metrics exist
        $metrics = DB::connection('testing')->table('lamet')
            ->where('name', 'test.counter')
            ->get();
        
        $this->assertEquals(2, $metrics->count());
    }

    public function test_default_tags_are_added()
    {
        Metrics::increment('test.counter');
        
        $count = Metrics::flush();
        
        $this->assertGreaterThan(0, $count);
        
        $metric = DB::connection('testing')->table('lamet')
            ->where('name', 'test.counter')
            ->first();
        
        $tags = json_decode($metric->tags, true);
        $this->assertArrayHasKey('environment', $tags);
        $this->assertArrayHasKey('app_name', $tags);
        $this->assertEquals('testing', $tags['environment']);
        $this->assertEquals('lamet-test', $tags['app_name']);
    }

    public function test_clean_old_metrics()
    {
        // Insert some old metrics directly
        DB::connection('testing')->table('lamet')->insert([
            [
                'name' => 'old.metric',
                'value' => 1.0,
                'tags' => json_encode([]),
                'type' => 'counter',
                'unit' => null,
                'recorded_at' => now()->subDays(31),
                'created_at' => now()->subDays(31),
                'updated_at' => now()->subDays(31),
            ],
            [
                'name' => 'new.metric',
                'value' => 1.0,
                'tags' => json_encode([]),
                'type' => 'counter',
                'unit' => null,
                'recorded_at' => now()->subDays(5),
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ],
        ]);
        
        // Clean metrics older than 30 days
        $deleted = Metrics::clean(30);
        
        $this->assertEquals(1, $deleted); // Only old metric should be deleted
        
        // Check remaining metrics
        $remaining = DB::connection('testing')->table('lamet')->count();
        $this->assertEquals(1, $remaining);
        
        $remainingMetric = DB::connection('testing')->table('lamet')->first();
        $this->assertEquals('new.metric', $remainingMetric->name);
    }
} 