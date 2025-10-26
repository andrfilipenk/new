<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create ERP Leaves Table
 */
class CreateErpLeavesTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('erp_leaves', function(Schema $table) {
            $table->id();
            $table->foreignId('employee_id');
            $table->foreignId('leave_type_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('days_requested');
            $table->string('status', 20)->default('pending'); // pending, approved, rejected, cancelled
            $table->text('reason')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            
            $table->foreign('employee_id')
                ->references('id')
                ->on('erp_employees')
                ->cascadeOnDelete();
                
            $table->foreign('leave_type_id')
                ->references('id')
                ->on('erp_leave_types')
                ->cascadeOnDelete();
                
            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            
            $table->index(['employee_id']);
            $table->index(['leave_type_id']);
            $table->index(['status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('erp_leaves');
    }
}
