<?php

use Core\Database\Migration;

/**
 * Create projects table
 */
class CreateProjectsTable extends Migration
{
    public function up(): void
    {
        $this->createTable('projects', function($table) {
            $table->id();
            $table->string('name', 255);
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->integer('client_id')->unsigned()->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status', 50)->default('Planning');
            $table->decimal('budget', 15, 2)->default(0);
            $table->string('priority', 50)->default('Medium');
            $table->integer('created_by')->unsigned()->nullable();
            $table->integer('updated_by')->unsigned()->nullable();
            $table->timestamps();
            $table->index('status');
            $table->index('client_id');
            $table->index('start_date');
            $table->index('end_date');
            $table->foreign('client_id')->references('id')->on('user')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('user')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('user')->onDelete('set null');
        });

        $this->createTable('orders', function($table) {
            $table->id();
            $table->integer('project_id')->unsigned();
            $table->string('order_number', 100)->unique();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status', 50)->default('Draft');
            $table->decimal('total_value', 15, 2)->default(0);
            $table->integer('created_by')->unsigned()->nullable();
            $table->integer('updated_by')->unsigned()->nullable();
            $table->timestamps();
            $table->index('project_id');
            $table->index('status');
            $table->index('order_number');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('user')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('user')->onDelete('set null');
        });

        $this->createTable('positions', function($table) {
            $table->id();
            $table->integer('order_id')->unsigned();
            $table->integer('position_number');
            $table->string('product_code', 100);
            $table->text('description');
            $table->decimal('quantity', 12, 2);
            $table->string('unit', 50);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->string('status', 50)->default('Pending');
            $table->integer('assigned_to')->unsigned()->nullable();
            $table->date('target_date')->nullable();
            $table->text('specifications')->nullable(); // json
            $table->timestamps();
            $table->index('order_id');
            $table->index('status');
            $table->index('product_code');
            $table->index('assigned_to');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('user')->onDelete('set null');
        });

        $this->createTable('order_phases', function($table) {
            $table->id();
            $table->integer('order_id')->unsigned();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 50)->default('Pending');
            $table->integer('completion_percentage')->default(0);
            $table->integer('sequence_order')->default(0);
            $table->timestamps();
            $table->index('order_id');
            $table->index('status');
            $table->index('sequence_order');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });

        $this->createTable('materials', function($table) {
            $table->id();
            $table->integer('order_id')->unsigned();
            $table->integer('position_id')->unsigned()->nullable();
            $table->string('material_type', 255);
            $table->string('specification', 255)->nullable();
            $table->decimal('quantity', 12, 2);
            $table->string('unit', 50);
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('total_cost', 15, 2);
            $table->string('supplier', 255)->nullable();
            $table->date('usage_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('order_id');
            $table->index('position_id');
            $table->index('material_type');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('position_id')->references('id')->on('positions')->onDelete('cascade');
        });

        $this->createTable('employee_activities', function($table) {
            $table->id();
            $table->integer('project_id')->unsigned()->nullable();
            $table->integer('order_id')->unsigned()->nullable();
            $table->integer('position_id')->unsigned()->nullable();
            $table->integer('employee_id')->unsigned();
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
            $table->foreign('employee_id')->references('id')->on('user')->onDelete('cascade');
        });

        $this->createTable('comments', function($table) {
            $table->id();
            $table->string('commentable_type', 255);
            $table->integer('commentable_id');
            $table->integer('user_id')->unsigned();
            $table->integer('parent_id')->unsigned()->nullable();
            $table->text('content');
            $table->text('attachments')->nullable(); // json
            $table->timestamps();
            $table->index(['commentable_type', 'commentable_id']);
            $table->index('user_id');
            $table->index('parent_id');
            $table->foreign('user_id')->references('id')->on('user')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('comments')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        $this->dropTable('projects');
        $this->dropTable('orders');
        $this->dropTable('positions');
        $this->dropTable('order_phases');
        $this->dropTable('materials');
        $this->dropTable('employee_activities');
        $this->dropTable('comments');

    }
}
