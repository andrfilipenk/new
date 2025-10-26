<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create ERP Inventory Table
 */
class CreateErpInventoryTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('erp_inventory', function(Schema $table) {
            $table->id();
            $table->foreignId('product_id');
            $table->foreignId('warehouse_id');
            $table->integer('quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->integer('available_quantity')->default(0);
            $table->decimal('average_cost', 12, 2)->default(0);
            $table->string('location', 100)->nullable(); // Bin/Shelf location
            $table->timestamps();
            
            $table->foreign('product_id')
                ->references('id')
                ->on('erp_products')
                ->cascadeOnDelete();
                
            $table->foreign('warehouse_id')
                ->references('id')
                ->on('erp_warehouses')
                ->cascadeOnDelete();
            
            $table->unique(['product_id', 'warehouse_id']);
            $table->index(['product_id']);
            $table->index(['warehouse_id']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('erp_inventory');
    }
}
