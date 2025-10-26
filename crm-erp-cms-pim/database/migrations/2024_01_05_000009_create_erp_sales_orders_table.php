<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create ERP Sales Orders Table
 */
class CreateErpSalesOrdersTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('erp_sales_orders', function(Schema $table) {
            $table->id();
            $table->string('so_number', 50)->unique();
            $table->foreignId('customer_id');
            $table->foreignId('warehouse_id');
            $table->date('order_date');
            $table->date('delivery_date')->nullable();
            $table->date('shipped_date')->nullable();
            $table->string('status', 20)->default('draft'); // draft, confirmed, processing, shipped, delivered, cancelled
            $table->string('shipping_method', 100)->nullable();
            $table->string('shipping_tracking', 100)->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('shipping_address')->nullable();
            $table->foreignId('created_by');
            $table->foreignId('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            
            $table->foreign('customer_id')
                ->references('id')
                ->on('erp_customers')
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
            
            $table->index(['so_number']);
            $table->index(['customer_id']);
            $table->index(['status']);
            $table->index(['order_date']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('erp_sales_orders');
    }
}
