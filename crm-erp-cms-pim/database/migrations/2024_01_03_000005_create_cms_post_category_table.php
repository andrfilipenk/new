<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create CMS Post Category Pivot Table
 */
class CreateCmsPostCategoryTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('cms_post_category', function(Schema $table) {
            $table->id();
            $table->foreignId('post_id');
            $table->foreignId('category_id');
            $table->timestamps();
            
            $table->foreign('post_id')
                ->references('id')
                ->on('cms_posts')
                ->cascadeOnDelete();
                
            $table->foreign('category_id')
                ->references('id')
                ->on('cms_categories')
                ->cascadeOnDelete();
            
            $table->unique(['post_id', 'category_id']);
            $table->index(['post_id']);
            $table->index(['category_id']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('cms_post_category');
    }
}
