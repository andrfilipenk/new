<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create PIM Categories Table
 */
class CreatePimCategoriesTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('pim_categories', function(Schema $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable();
            $table->integer('level')->default(0);
            $table->string('path', 500)->nullable(); // /parent/child/current
            $table->integer('order')->default(0);
            $table->string('image', 255)->nullable();
            $table->string('meta_title', 200)->nullable();
            $table->text('meta_description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('product_count')->default(0);
            $table->timestamps();
            
            $table->foreign('parent_id')
                ->references('id')
                ->on('pim_categories')
                ->cascadeOnDelete();
            
            $table->index(['slug']);
            $table->index(['parent_id']);
            $table->index(['is_active']);
            $table->index(['path']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('pim_categories');
    }
}
