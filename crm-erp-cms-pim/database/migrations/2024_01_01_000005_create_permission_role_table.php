<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create Permission Role Pivot Table
 */
class CreatePermissionRoleTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('permission_role', function(Schema $table) {
            $table->id();
            $table->foreignId('permission_id');
            $table->foreignId('role_id');
            $table->timestamps();
            
            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->cascadeOnDelete();
                
            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->cascadeOnDelete();
            
            $table->unique(['permission_id', 'role_id']);
            $table->index(['role_id']);
        });
    }
    
    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('permission_role');
    }
}
