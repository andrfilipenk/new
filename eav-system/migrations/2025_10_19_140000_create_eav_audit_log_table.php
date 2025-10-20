<?php

use Core\Database\Migration;
use Core\Database\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('eav_audit_log', function ($table) {
            $table->id('log_id');
            $table->string('event_type', 100);
            $table->string('entity_type', 50)->nullable();
            $table->integer('entity_id')->unsigned()->nullable();
            $table->integer('user_id')->unsigned()->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->json('request_data')->nullable();
            $table->smallInteger('response_status')->unsigned()->nullable();
            $table->integer('execution_time')->unsigned()->nullable()->comment('milliseconds');
            $table->datetime('created_at');
            
            // Indexes
            $table->index('event_type');
            $table->index(['entity_type', 'entity_id'], 'idx_entity_audit');
            $table->index('user_id');
            $table->index('created_at');
            $table->index('response_status');
        });
    }

    public function down(): void
    {
        $this->dropTable('eav_audit_log');
    }
};
