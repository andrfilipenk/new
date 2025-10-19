<?php

use Core\Database\Migration;
use Core\Database\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eav_reports', function ($table) {
            $table->id('report_id');
            $table->string('report_name', 200);
            $table->string('report_type', 50);
            $table->integer('entity_type_id')->unsigned()->nullable();
            $table->json('configuration');
            $table->integer('created_by')->unsigned();
            $table->tinyInteger('is_scheduled')->default(0);
            $table->json('schedule_config')->nullable();
            $table->datetime('last_run_at')->nullable();
            $table->datetime('created_at');
            $table->datetime('updated_at');
            
            // Indexes
            $table->index('entity_type_id');
            $table->index('created_by');
            $table->index('is_scheduled');
            $table->index('report_type');
            
            // Foreign keys
            $table->foreignKey('entity_type_id', 'eav_entity_type', 'entity_type_id', 'SET NULL', 'CASCADE');
            $table->foreignKey('created_by', 'users', 'id', 'CASCADE', 'CASCADE');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eav_reports');
    }
};
