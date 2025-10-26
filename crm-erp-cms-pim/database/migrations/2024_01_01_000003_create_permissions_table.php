<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create Permissions Table
 */
class CreatePermissionsTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('permissions', function(Schema $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->string('module', 50)->comment('Module name (crm, erp, cms, pim, etc.)');
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->unique(['slug']);
            $table->index(['module']);
        });
    }
    
    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('permissions');
    }
}
