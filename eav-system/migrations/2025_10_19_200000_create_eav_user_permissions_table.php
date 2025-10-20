<?php

use Core\Database\Migration;
use Core\Database\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('eav_user_permissions', function ($table) {
            $table->id('permission_id');
            $table->integer('user_id')->unsigned();
            $table->string('role', 50);
            $table->integer('entity_type_id')->unsigned()->nullable();
            $table->json('permissions');
            $table->json('row_filter')->nullable();
            $table->json('hidden_attributes')->nullable();
            $table->datetime('created_at');
            $table->datetime('updated_at');
            
            // Indexes
            $table->index('user_id');
            $table->index('role');
            $table->index('entity_type_id');
            $table->index(['user_id', 'entity_type_id'], 'idx_user_entity_perms');
            
            // Foreign keys
            $table->foreignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
            $table->foreignKey('entity_type_id', 'eav_entity_type', 'entity_type_id', 'CASCADE', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('eav_user_permissions');
    }
};
