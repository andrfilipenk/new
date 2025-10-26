<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create PIM Price List Items Table
 */
class CreatePimPriceListItemsTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('pim_price_list_items', function(Schema $table) {
            $table->id();
            $table->foreignId('price_list_id');
            $table->foreignId('product_id');
            $table->foreignId('variant_id')->nullable();
            $table->decimal('price', 12, 2);
            $table->decimal('cost', 12, 2)->nullable();
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('final_price', 12, 2);
            $table->integer('min_quantity')->default(1);
            $table->timestamps();
            
            $table->foreign('price_list_id')
                ->references('id')
                ->on('pim_price_lists')
                ->cascadeOnDelete();
                
            $table->foreign('product_id')
                ->references('id')
                ->on('pim_products')
                ->cascadeOnDelete();
                
            $table->foreign('variant_id')
                ->references('id')
                ->on('pim_product_variants')
                ->cascadeOnDelete();
            
            $table->index(['price_list_id']);
            $table->index(['product_id']);
            $table->index(['variant_id']);
            $table->unique(['price_list_id', 'product_id', 'variant_id', 'min_quantity'], 'price_list_product_variant_unique');
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('pim_price_list_items');
    }
}
