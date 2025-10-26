<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create CMS Comments Table
 */
class CreateCmsCommentsTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('cms_comments', function(Schema $table) {
            $table->id();
            $table->foreignId('post_id');
            $table->foreignId('parent_id')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->string('author_name', 100)->nullable();
            $table->string('author_email', 100)->nullable();
            $table->string('author_ip', 45)->nullable();
            $table->text('content');
            $table->string('status', 20)->default('pending'); // pending, approved, spam, trash
            $table->integer('upvotes')->default(0);
            $table->integer('downvotes')->default(0);
            $table->timestamps();
            
            $table->foreign('post_id')
                ->references('id')
                ->on('cms_posts')
                ->cascadeOnDelete();
                
            $table->foreign('parent_id')
                ->references('id')
                ->on('cms_comments')
                ->cascadeOnDelete();
                
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            
            $table->index(['post_id']);
            $table->index(['parent_id']);
            $table->index(['user_id']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('cms_comments');
    }
}
