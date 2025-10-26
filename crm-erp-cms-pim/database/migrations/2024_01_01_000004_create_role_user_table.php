<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create Role User Pivot Table
 */
class CreateRoleUserTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('role_user', function(Schema $table) {
            $table->id();
            $table->foreignId('role_id');
            $table->foreignId('user_id');
            $table->timestamps();
            
            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->cascadeOnDelete();
                
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            
            $table->unique(['role_id', 'user_id']);
            $table->index(['user_id']);
        });
    }
    
    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('role_user');
    }
}
