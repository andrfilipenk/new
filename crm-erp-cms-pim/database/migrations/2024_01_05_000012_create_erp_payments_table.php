<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create ERP Payments Table
 */
class CreateErpPaymentsTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('erp_payments', function(Schema $table) {
            $table->id();
            $table->string('payment_number', 50)->unique();
            $table->string('payment_type', 20); // receipt, payment
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('vendor_id')->nullable();
            $table->foreignId('invoice_id')->nullable();
            $table->date('payment_date');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 50); // cash, check, bank_transfer, credit_card
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
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
                
            $table->foreign('invoice_id')
                ->references('id')
                ->on('erp_invoices')
                ->nullOnDelete();
                
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            
            $table->index(['payment_number']);
            $table->index(['payment_type']);
            $table->index(['customer_id']);
            $table->index(['vendor_id']);
            $table->index(['invoice_id']);
            $table->index(['payment_date']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('erp_payments');
    }
}
