<?php

use Core\Database\Migration;
use Core\Database\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('eav_webhooks', function ($table) {
            $table->id('webhook_id');
            $table->string('webhook_name', 200);
            $table->string('target_url', 500);
            $table->json('event_types');
            $table->integer('entity_type_id')->unsigned()->nullable();
            $table->string('secret_key', 255)->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->integer('max_retries')->default(3);
            $table->json('headers')->nullable();
            $table->datetime('last_triggered_at')->nullable();
            $table->datetime('created_at');
            $table->datetime('updated_at');
            
            // Indexes
            $table->index('entity_type_id');
            $table->index('is_active');
            
            // Foreign keys
            $table->foreignKey('entity_type_id', 'eav_entity_type', 'entity_type_id', 'CASCADE', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('eav_webhooks');
    }
};
