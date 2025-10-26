<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create PIM Product Categories Pivot Table
 */
class CreatePimProductCategoriesTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('pim_product_categories', function(Schema $table) {
            $table->id();
            $table->foreignId('product_id');
            $table->foreignId('category_id');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            
            $table->foreign('product_id')
                ->references('id')
                ->on('pim_products')
                ->cascadeOnDelete();
                
            $table->foreign('category_id')
                ->references('id')
                ->on('pim_categories')
                ->cascadeOnDelete();
            
            $table->unique(['product_id', 'category_id']);
            $table->index(['product_id']);
            $table->index(['category_id']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('pim_product_categories');
    }
}
