<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create CMS Menus Table
 */
class CreateCmsMenusTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('cms_menus', function(Schema $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->string('location', 50); // header, footer, sidebar, etc.
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['slug']);
            $table->index(['location']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('cms_menus');
    }
}
