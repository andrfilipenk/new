<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create CMS Revisions Table
 */
class CreateCmsRevisionsTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('cms_revisions', function(Schema $table) {
            $table->id();
            $table->string('revisionable_type', 100); // Page, Post
            $table->integer('revisionable_id');
            $table->text('title')->nullable();
            $table->longText('content')->nullable();
            $table->text('excerpt')->nullable();
            $table->json('metadata')->nullable(); // Store other fields as JSON
            $table->foreignId('user_id');
            $table->string('revision_type', 20)->default('auto'); // auto, manual
            $table->timestamps();
            
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            
            $table->index(['revisionable_type', 'revisionable_id']);
            $table->index(['user_id']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('cms_revisions');
    }
}
