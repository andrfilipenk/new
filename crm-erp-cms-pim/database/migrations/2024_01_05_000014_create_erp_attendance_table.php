<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create ERP Attendance Table
 */
class CreateErpAttendanceTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('erp_attendance', function(Schema $table) {
            $table->id();
            $table->foreignId('employee_id');
            $table->date('attendance_date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->integer('work_hours')->default(0); // in minutes
            $table->integer('overtime_hours')->default(0); // in minutes
            $table->string('status', 20); // present, absent, late, half_day, on_leave
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->timestamps();
            
            $table->foreign('employee_id')
                ->references('id')
                ->on('erp_employees')
                ->cascadeOnDelete();
                
            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            
            $table->unique(['employee_id', 'attendance_date']);
            $table->index(['employee_id']);
            $table->index(['attendance_date']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('erp_attendance');
    }
}
