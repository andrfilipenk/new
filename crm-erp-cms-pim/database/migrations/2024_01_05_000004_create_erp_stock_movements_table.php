<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create ERP Stock Movements Table
 */
class CreateErpStockMovementsTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('erp_stock_movements', function(Schema $table) {
            $table->id();
            $table->string('reference_no', 50)->unique();
            $table->foreignId('product_id');
            $table->foreignId('warehouse_id');
            $table->string('type', 30); // in, out, transfer, adjustment
            $table->integer('quantity');
            $table->integer('quantity_before')->default(0);
            $table->integer('quantity_after')->default(0);
            $table->decimal('cost', 12, 2)->nullable();
            $table->string('reference_type', 50)->nullable(); // purchase_order, sales_order, etc.
            $table->integer('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by');
            $table->timestamp('movement_date');
            $table->timestamps();
            
            $table->foreign('product_id')
                ->references('id')
                ->on('erp_products')
                ->cascadeOnDelete();
                
            $table->foreign('warehouse_id')
                ->references('id')
                ->on('erp_warehouses')
                ->cascadeOnDelete();
                
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            
            $table->index(['reference_no']);
            $table->index(['product_id']);
            $table->index(['warehouse_id']);
            $table->index(['type']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('erp_stock_movements');
    }
}
