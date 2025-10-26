<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create CMS Post Tag Pivot Table
 */
class CreateCmsPostTagTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('cms_post_tag', function(Schema $table) {
            $table->id();
            $table->foreignId('post_id');
            $table->foreignId('tag_id');
            $table->timestamps();
            
            $table->foreign('post_id')
                ->references('id')
                ->on('cms_posts')
                ->cascadeOnDelete();
                
            $table->foreign('tag_id')
                ->references('id')
                ->on('cms_tags')
                ->cascadeOnDelete();
            
            $table->unique(['post_id', 'tag_id']);
            $table->index(['post_id']);
            $table->index(['tag_id']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('cms_post_tag');
    }
}
