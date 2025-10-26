<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create PIM Product Images Table
 */
class CreatePimProductImagesTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('pim_product_images', function(Schema $table) {
            $table->id();
            $table->foreignId('product_id');
            $table->foreignId('variant_id')->nullable();
            $table->string('image_path', 255);
            $table->string('alt_text', 200)->nullable();
            $table->string('title', 200)->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_main')->default(false);
            $table->timestamps();
            
            $table->foreign('product_id')
                ->references('id')
                ->on('pim_products')
                ->cascadeOnDelete();
                
            $table->foreign('variant_id')
                ->references('id')
                ->on('pim_product_variants')
                ->cascadeOnDelete();
            
            $table->index(['product_id']);
            $table->index(['variant_id']);
            $table->index(['product_id', 'order']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('pim_product_images');
    }
}
