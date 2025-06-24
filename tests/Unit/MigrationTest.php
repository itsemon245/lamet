<?php

namespace Itsemon245\Lamet\Tests\Unit;

use Illuminate\Support\Facades\Schema;
use Itsemon245\Lamet\Tests\TestCase;

class MigrationTest extends TestCase
{
    /**
     * Test that the metrics table is created.
     */
    public function test_metrics_table_is_created()
    {
        $this->assertTrue(Schema::connection('testing')->hasTable($this->getMetricsTableName()));
    }

    /**
     * Test that the metrics table has all required columns.
     */
    public function test_metrics_table_has_required_columns()
    {
        $columns = Schema::connection('testing')->getColumnListing($this->getMetricsTableName());

        $requiredColumns = [
            'id',
            'name',
            'value',
            'tags',
            'type',
            'unit',
            'recorded_at',
            'created_at',
            'updated_at',
        ];

        foreach ($requiredColumns as $column) {
            $this->assertContains($column, $columns, "Column {$column} should exist in {$this->getMetricsTableName()} table");
        }
    }

    /**
     * Test that the metrics table has required indexes.
     */
    public function test_metrics_table_has_indexes()
    {
        $indexes = Schema::connection('testing')->getIndexes($this->getMetricsTableName());

        // Check for basic indexes
        $indexNames = array_column($indexes, 'name');

        $this->assertContains($this->getMetricsTableName().'_name_time_idx', $indexNames);
        $this->assertContains($this->getMetricsTableName().'_type_time_idx', $indexNames);
    }
}
