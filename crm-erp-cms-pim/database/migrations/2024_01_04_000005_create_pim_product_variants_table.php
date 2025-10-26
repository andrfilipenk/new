<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create PIM Product Variants Table
 */
class CreatePimProductVariantsTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('pim_product_variants', function(Schema $table) {
            $table->id();
            $table->foreignId('product_id');
            $table->string('sku', 50)->unique();
            $table->string('name', 200);
            $table->text('option_values')->nullable(); // JSON: {"size": "M", "color": "Red"}
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->decimal('compare_at_price', 12, 2)->nullable();
            $table->decimal('weight', 10, 3)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->string('image', 255)->nullable();
            $table->string('barcode', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('product_id')
                ->references('id')
                ->on('pim_products')
                ->cascadeOnDelete();
            
            $table->index(['product_id']);
            $table->index(['sku']);
            $table->index(['barcode']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('pim_product_variants');
    }
}
