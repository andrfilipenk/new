<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create PIM Attributes Table
 */
class CreatePimAttributesTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('pim_attributes', function(Schema $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->string('type', 30); // text, textarea, number, decimal, boolean, date, select, multiselect, file, image
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_unique')->default(false);
            $table->boolean('is_searchable')->default(true);
            $table->boolean('is_filterable')->default(true);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_system')->default(false);
            $table->text('validation_rules')->nullable(); // JSON
            $table->text('options')->nullable(); // JSON for select/multiselect
            $table->string('default_value', 255)->nullable();
            $table->integer('order')->default(0);
            $table->string('group', 50)->default('general'); // general, specifications, seo, etc.
            $table->timestamps();
            
            $table->index(['code']);
            $table->index(['type']);
            $table->index(['group']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('pim_attributes');
    }
}
