<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create ERP Employees Table
 */
class CreateErpEmployeesTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('erp_employees', function(Schema $table) {
            $table->id();
            $table->string('employee_code', 50)->unique();
            $table->foreignId('user_id')->nullable();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 100)->unique();
            $table->string('phone', 50)->nullable();
            $table->string('mobile', 50)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('department', 100)->nullable();
            $table->string('position', 100)->nullable();
            $table->date('hire_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->string('employment_type', 50)->nullable(); // full_time, part_time, contract
            $table->string('status', 20)->default('active'); // active, inactive, terminated
            $table->decimal('salary', 12, 2)->nullable();
            $table->string('bank_account', 100)->nullable();
            $table->string('tax_id', 50)->nullable();
            $table->foreignId('manager_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
                
            $table->foreign('manager_id')
                ->references('id')
                ->on('erp_employees')
                ->nullOnDelete();
            
            $table->index(['employee_code']);
            $table->index(['email']);
            $table->index(['department']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('erp_employees');
    }
}
