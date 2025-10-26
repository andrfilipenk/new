<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create Sessions Table
 */
class CreateSessionsTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('sessions', function(Schema $table) {
            $table->string('id', 255);
            $table->foreignId('user_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('payload');
            $table->integer('last_activity');
            
            $table->index(['id']);
            $table->index(['user_id']);
            $table->index(['last_activity']);
        });
    }
    
    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('sessions');
    }
}
