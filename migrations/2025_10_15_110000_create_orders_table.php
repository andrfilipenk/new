<?php

use Core\Database\Migration;

/**
 * Create orders table
 */
class CreateOrdersTable extends Migration
{
    public function up(): void
    {
        $this->createTable('orders', function($table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('order_number', 100)->unique();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status', 50)->default('Draft');
            $table->decimal('total_value', 15, 2)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('project_id');
            $table->index('status');
            $table->index('order_number');
            
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        $this->dropTable('orders');
    }
}
