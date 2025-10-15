<?php

use Core\Database\Migration;

/**
 * Create order_phases table
 */
class CreateOrderPhasesTable extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        $this->dropTable('order_phases');
    }
}
