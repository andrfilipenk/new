<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create CMS Menu Items Table
 */
class CreateCmsMenuItemsTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('cms_menu_items', function(Schema $table) {
            $table->id();
            $table->foreignId('menu_id');
            $table->foreignId('parent_id')->nullable();
            $table->string('title', 100);
            $table->string('url', 255)->nullable();
            $table->string('type', 50); // page, post, category, custom, external
            $table->integer('target_id')->nullable(); // ID of linked page/post/category
            $table->string('target', 20)->default('_self'); // _self, _blank
            $table->string('css_class', 100)->nullable();
            $table->string('icon', 50)->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('menu_id')
                ->references('id')
                ->on('cms_menus')
                ->cascadeOnDelete();
                
            $table->foreign('parent_id')
                ->references('id')
                ->on('cms_menu_items')
                ->cascadeOnDelete();
            
            $table->index(['menu_id']);
            $table->index(['parent_id']);
            $table->index(['menu_id', 'order']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('cms_menu_items');
    }
}
