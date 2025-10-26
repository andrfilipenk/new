<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create CMS Categories Table
 */
class CreateCmsCategoriesTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('cms_categories', function(Schema $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable();
            $table->integer('order')->default(0);
            $table->string('meta_title', 200)->nullable();
            $table->text('meta_description')->nullable();
            $table->string('image', 255)->nullable();
            $table->integer('post_count')->default(0);
            $table->timestamps();
            
            $table->foreign('parent_id')
                ->references('id')
                ->on('cms_categories')
                ->cascadeOnDelete();
            
            $table->index(['slug']);
            $table->index(['parent_id']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('cms_categories');
    }
}
