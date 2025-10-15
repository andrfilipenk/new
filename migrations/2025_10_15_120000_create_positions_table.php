<?php

use Core\Database\Migration;

/**
 * Create positions table
 */
class CreatePositionsTable extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        $this->dropTable('positions');
    }
}
