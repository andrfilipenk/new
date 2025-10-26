<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create ERP Leave Types Table
 */
class CreateErpLeaveTypesTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('erp_leave_types', function(Schema $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->integer('days_per_year')->default(0);
            $table->boolean('is_paid')->default(true);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['code']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('erp_leave_types');
    }
}
