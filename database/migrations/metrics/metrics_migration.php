<?php

use Illuminate\Database\Schema\Blueprint;
use Itsemon245\Lamet\Database\Migrations\MetricsMigration;

return new class extends MetricsMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $schema = $this->getMetricsSchema();
        $tableName = $this->getTableName();

        $schema->create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->index();
            $table->decimal('value', 15, 6);

            // Use jsonb for PostgreSQL, json for others
            $this->addJsonColumn($table, 'tags');

            $table->string('type', 50)->default('counter')->index();
            $table->string('unit', 20)->nullable();
            $table->timestamp('recorded_at')->useCurrent()->index();
            $table->timestamps();

            // Optimized indexes for Grafana queries
            $table->index(['name', 'recorded_at'], 'metrics_name_time_idx');
            $table->index(['type', 'recorded_at'], 'metrics_type_time_idx');

            // Add GIN index for jsonb tags in PostgreSQL
            $this->addJsonIndex($table, 'tags', 'metrics_tags_gin_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $schema = $this->getMetricsSchema();
        $tableName = $this->getTableName();

        $schema->dropIfExists($tableName);
    }
};
