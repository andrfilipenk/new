<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create ERP Invoices Table
 */
class CreateErpInvoicesTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('erp_invoices', function(Schema $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->string('invoice_type', 20); // sales, purchase
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('vendor_id')->nullable();
            $table->foreignId('sales_order_id')->nullable();
            $table->foreignId('purchase_order_id')->nullable();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('draft'); // draft, sent, partial, paid, overdue, cancelled
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_due', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->foreignId('created_by');
            $table->timestamps();
            
            $table->foreign('customer_id')
                ->references('id')
                ->on('erp_customers')
                ->cascadeOnDelete();
                
            $table->foreign('vendor_id')
                ->references('id')
                ->on('erp_vendors')
                ->cascadeOnDelete();
                
            $table->foreign('sales_order_id')
                ->references('id')
                ->on('erp_sales_orders')
                ->nullOnDelete();
                
            $table->foreign('purchase_order_id')
                ->references('id')
                ->on('erp_purchase_orders')
                ->nullOnDelete();
                
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            
            $table->index(['invoice_number']);
            $table->index(['invoice_type']);
            $table->index(['customer_id']);
            $table->index(['vendor_id']);
            $table->index(['status']);
            $table->index(['invoice_date']);
            $table->index(['due_date']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('erp_invoices');
    }
}
