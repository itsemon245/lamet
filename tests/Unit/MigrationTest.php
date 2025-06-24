<?php

namespace Itsemon245\Lamet\Tests\Unit;

use Illuminate\Support\Facades\Schema;
use Itsemon245\Lamet\Tests\TestCase;

class MigrationTest extends TestCase
{
    public function test_lamet_table_is_created()
    {
        $this->assertTrue(Schema::connection('testing')->hasTable('lamet'));
    }

    public function test_lamet_table_has_required_columns()
    {
        $columns = Schema::connection('testing')->getColumnListing('lamet');

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
            $this->assertContains($column, $columns, "Column {$column} should exist in lamet table");
        }
    }

    public function test_lamet_table_has_indexes()
    {
        $indexes = Schema::connection('testing')->getIndexes('lamet');

        // Check for basic indexes
        $indexNames = array_column($indexes, 'name');

        $this->assertContains('lamet_name_time_idx', $indexNames);
        $this->assertContains('lamet_type_time_idx', $indexNames);
    }
}
