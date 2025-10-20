<?php

use Core\Database\Migration;
use Core\Database\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('eav_export_jobs', function ($table) {
            $table->id('job_id');
            $table->integer('entity_type_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->string('export_name', 255);
            $table->string('file_path', 500)->nullable();
            $table->enum('format', ['csv', 'xlsx', 'json', 'xml'])->default('csv');
            $table->json('filter_config')->nullable();
            $table->json('column_config')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->integer('total_rows')->default(0);
            $table->datetime('started_at')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->datetime('created_at');
            
            // Indexes
            $table->index('entity_type_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
            
            // Foreign keys
            $table->foreignKey('entity_type_id', 'eav_entity_type', 'entity_type_id', 'CASCADE', 'CASCADE');
            $table->foreignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('eav_export_jobs');
    }
};
