<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create ERP Purchase Order Items Table
 */
class CreateErpPurchaseOrderItemsTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('erp_purchase_order_items', function(Schema $table) {
            $table->id();
            $table->foreignId('purchase_order_id');
            $table->foreignId('product_id');
            $table->integer('quantity');
            $table->integer('received_quantity')->default(0);
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_rate', 5, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('erp_purchase_orders')
                ->cascadeOnDelete();
                
            $table->foreign('product_id')
                ->references('id')
                ->on('erp_products')
                ->cascadeOnDelete();
            
            $table->index(['purchase_order_id']);
            $table->index(['product_id']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('erp_purchase_order_items');
    }
}
