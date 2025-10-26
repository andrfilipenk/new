<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create PIM Products Table
 */
class CreatePimProductsTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('pim_products', function(Schema $table) {
            $table->id();
            $table->string('sku', 50)->unique();
            $table->string('name', 200);
            $table->string('slug', 200)->unique();
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->foreignId('brand_id')->nullable();
            $table->string('type', 20)->default('simple'); // simple, configurable, bundle, virtual
            $table->string('status', 20)->default('draft'); // draft, active, inactive, discontinued
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->decimal('compare_at_price', 12, 2)->nullable();
            $table->string('tax_class', 50)->nullable();
            $table->decimal('weight', 10, 3)->nullable();
            $table->string('weight_unit', 10)->default('kg');
            $table->string('dimensions', 100)->nullable(); // LxWxH
            $table->string('dimension_unit', 10)->default('cm');
            $table->string('main_image', 255)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('track_inventory')->default(true);
            $table->integer('stock_quantity')->default(0);
            $table->integer('low_stock_threshold')->nullable();
            $table->integer('view_count')->default(0);
            $table->string('meta_title', 200)->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('brand_id')
                ->references('id')
                ->on('pim_brands')
                ->nullOnDelete();
                
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            
            $table->index(['sku']);
            $table->index(['slug']);
            $table->index(['brand_id']);
            $table->index(['type']);
            $table->index(['status']);
            $table->index(['is_featured']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('pim_products');
    }
}
