<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create ERP Payroll Table
 */
class CreateErpPayrollTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('erp_payroll', function(Schema $table) {
            $table->id();
            $table->foreignId('employee_id');
            $table->string('payroll_period', 20); // month-year format (e.g., 2024-01)
            $table->date('pay_date');
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('allowances', 12, 2)->default(0);
            $table->decimal('overtime_pay', 12, 2)->default(0);
            $table->decimal('bonuses', 12, 2)->default(0);
            $table->decimal('gross_salary', 12, 2)->default(0);
            $table->decimal('tax_deduction', 12, 2)->default(0);
            $table->decimal('insurance_deduction', 12, 2)->default(0);
            $table->decimal('other_deductions', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2)->default(0);
            $table->string('status', 20)->default('draft'); // draft, processed, paid
            $table->text('notes')->nullable();
            $table->foreignId('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            $table->foreign('employee_id')
                ->references('id')
                ->on('erp_employees')
                ->cascadeOnDelete();
                
            $table->foreign('processed_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            
            $table->unique(['employee_id', 'payroll_period']);
            $table->index(['employee_id']);
            $table->index(['payroll_period']);
            $table->index(['status']);
            $table->index(['pay_date']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('erp_payroll');
    }
}
