<?php

use Core\Database\Migration;
use Core\Database\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eav_entity_versions', function ($table) {
            $table->id('version_id');
            $table->integer('entity_id')->unsigned();
            $table->integer('entity_type_id')->unsigned();
            $table->integer('version_number')->unsigned();
            $table->json('attribute_snapshots');
            $table->json('changed_attributes')->nullable();
            $table->integer('user_id')->unsigned()->nullable();
            $table->text('change_description')->nullable();
            $table->datetime('created_at');
            
            // Indexes
            $table->index(['entity_id', 'version_number'], 'idx_entity_version');
            $table->index('entity_type_id');
            $table->index('user_id');
            $table->index('created_at');
            
            // Foreign keys
            $table->foreignKey('entity_type_id', 'eav_entity_type', 'entity_type_id', 'CASCADE', 'CASCADE');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eav_entity_versions');
    }
};
