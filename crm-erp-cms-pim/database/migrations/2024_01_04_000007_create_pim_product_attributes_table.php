<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create PIM Product Attributes Table
 */
class CreatePimProductAttributesTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('pim_product_attributes', function(Schema $table) {
            $table->id();
            $table->foreignId('product_id');
            $table->foreignId('attribute_id');
            $table->text('value')->nullable();
            $table->text('value_text')->nullable(); // For textarea
            $table->decimal('value_number', 12, 4)->nullable(); // For number/decimal
            $table->date('value_date')->nullable(); // For date
            $table->boolean('value_boolean')->nullable(); // For boolean
            $table->timestamps();
            
            $table->foreign('product_id')
                ->references('id')
                ->on('pim_products')
                ->cascadeOnDelete();
                
            $table->foreign('attribute_id')
                ->references('id')
                ->on('pim_attributes')
                ->cascadeOnDelete();
            
            $table->unique(['product_id', 'attribute_id']);
            $table->index(['product_id']);
            $table->index(['attribute_id']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('pim_product_attributes');
    }
}
