<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create PIM Brands Table
 */
class CreatePimBrandsTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('pim_brands', function(Schema $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->string('logo', 255)->nullable();
            $table->string('website', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['slug']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('pim_brands');
    }
}
