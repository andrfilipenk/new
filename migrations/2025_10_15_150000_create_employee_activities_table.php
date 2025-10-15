<?php

use Core\Database\Migration;

/**
 * Create employee_activities table
 */
class CreateEmployeeActivitiesTable extends Migration
{
    public function up(): void
    {
        $this->createTable('employee_activities', function($table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('position_id')->nullable();
            $table->unsignedBigInteger('employee_id');
            $table->string('activity_type', 100);
            $table->decimal('hours', 8, 2);
            $table->date('activity_date');
            $table->string('description', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('project_id');
            $table->index('order_id');
            $table->index('position_id');
            $table->index('employee_id');
            $table->index('activity_date');
            
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('position_id')->references('id')->on('positions')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        $this->dropTable('employee_activities');
    }
}
