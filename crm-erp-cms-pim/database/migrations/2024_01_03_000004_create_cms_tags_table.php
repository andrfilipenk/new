<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create CMS Tags Table
 */
class CreateCmsTagsTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('cms_tags', function(Schema $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->integer('post_count')->default(0);
            $table->timestamps();
            
            $table->index(['slug']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('cms_tags');
    }
}
