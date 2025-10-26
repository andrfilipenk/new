<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create ERP Products Table
 */
class CreateErpProductsTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('erp_products', function(Schema $table) {
            $table->id();
            $table->string('sku', 50)->unique();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->string('unit', 20)->default('pcs'); // pcs, kg, liter, etc.
            $table->decimal('cost', 12, 2)->default(0);
            $table->decimal('price', 12, 2)->default(0);
            $table->boolean('track_inventory')->default(true);
            $table->integer('reorder_level')->nullable();
            $table->integer('reorder_quantity')->nullable();
            $table->string('barcode', 100)->nullable();
            $table->string('category', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['sku']);
            $table->index(['barcode']);
            $table->index(['category']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('erp_products');
    }
}
