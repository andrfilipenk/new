<?php

use Core\Database\Migration;

/**
 * Create comments table
 */
class CreateCommentsTable extends Migration
{
    public function up(): void
    {
        $this->createTable('comments', function($table) {
            $table->id();
            $table->string('commentable_type', 255);
            $table->unsignedBigInteger('commentable_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->text('content');
            $table->json('attachments')->nullable();
            $table->timestamps();

            $table->index(['commentable_type', 'commentable_id']);
            $table->index('user_id');
            $table->index('parent_id');
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('comments')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        $this->dropTable('comments');
    }
}
