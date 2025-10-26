<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create CMS Pages Table
 */
class CreateCmsPagesTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('cms_pages', function(Schema $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('slug', 200);
            $table->text('content')->nullable();
            $table->text('excerpt')->nullable();
            $table->string('template', 100)->default('default');
            $table->string('status', 20)->default('draft'); // draft, published, scheduled, archived
            $table->enum('visibility', ['public', 'private', 'password'])->default('public');
            $table->string('password', 255)->nullable();
            $table->foreignId('parent_id')->nullable();
            $table->foreignId('author_id');
            $table->integer('order')->default(0);
            $table->string('meta_title', 200)->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->string('featured_image', 255)->nullable();
            $table->boolean('is_homepage')->default(false);
            $table->boolean('show_in_menu')->default(true);
            $table->integer('view_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('parent_id')
                ->references('id')
                ->on('cms_pages')
                ->nullOnDelete();
                
            $table->foreign('author_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            
            $table->index(['slug']);
            $table->index(['status']);
            $table->index(['parent_id']);
            $table->index(['author_id']);
            $table->index(['published_at']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('cms_pages');
    }
}
