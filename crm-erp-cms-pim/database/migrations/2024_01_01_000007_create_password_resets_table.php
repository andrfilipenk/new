<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create Password Resets Table
 */
class CreatePasswordResetsTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('password_resets', function(Schema $table) {
            $table->string('email');
            $table->string('token');
            $table->timestamp('created_at')->nullable();
            
            $table->index(['email']);
            $table->index(['token']);
        });
    }
    
    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('password_resets');
    }
}
