<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create ERP Purchase Orders Table
 */
class CreateErpPurchaseOrdersTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('erp_purchase_orders', function(Schema $table) {
            $table->id();
            $table->string('po_number', 50)->unique();
            $table->foreignId('vendor_id');
            $table->foreignId('warehouse_id');
            $table->date('order_date');
            $table->date('expected_date')->nullable();
            $table->date('received_date')->nullable();
            $table->string('status', 20)->default('draft'); // draft, sent, partial, received, cancelled
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('payment_terms', 100)->nullable();
            $table->foreignId('created_by');
            $table->foreignId('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            
            $table->foreign('vendor_id')
                ->references('id')
                ->on('erp_vendors')
                ->cascadeOnDelete();
                
            $table->foreign('warehouse_id')
                ->references('id')
                ->on('erp_warehouses')
                ->cascadeOnDelete();
                
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
                
            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            
            $table->index(['po_number']);
            $table->index(['vendor_id']);
            $table->index(['status']);
            $table->index(['order_date']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('erp_purchase_orders');
    }
}
