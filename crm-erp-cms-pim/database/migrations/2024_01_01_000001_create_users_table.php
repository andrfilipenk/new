<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create Users Table
 */
class CreateUsersTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->createTable('users', function(Schema $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->string('remember_token', 100)->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['email']);
            $table->index(['is_active']);
        });
    }
    
    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('users');
    }
}
