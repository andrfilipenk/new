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
            $table->integer('commentable_id');
            $table->integer('user_id')->unsigned();
            $table->integer('parent_id')->unsigned()->nullable();
            $table->text('content');
            $table->text('attachments')->nullable(); // json
            $table->timestamps();

            $table->index(['commentable_type', 'commentable_id']);
            $table->index('user_id');
            $table->index('parent_id');
            
            $table->foreign('user_id')->references('id')->on('user')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('comments')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        $this->dropTable('comments');
    }
}
