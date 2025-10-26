<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create CMS Posts Table
 */
class CreateCmsPostsTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('cms_posts', function(Schema $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('slug', 200)->unique();
            $table->text('content')->nullable();
            $table->text('excerpt')->nullable();
            $table->string('status', 20)->default('draft'); // draft, published, scheduled, archived
            $table->enum('comment_status', ['open', 'closed'])->default('open');
            $table->foreignId('author_id');
            $table->string('meta_title', 200)->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->string('featured_image', 255)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_sticky')->default(false);
            $table->integer('view_count')->default(0);
            $table->integer('comment_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('author_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            
            $table->index(['slug']);
            $table->index(['status']);
            $table->index(['author_id']);
            $table->index(['published_at']);
            $table->index(['is_featured']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('cms_posts');
    }
}
