<?php

use Core\Database\Migration;

/**
 * Create materials table
 */
class CreateMaterialsTable extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        $this->dropTable('materials');
    }
}
