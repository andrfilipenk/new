<?php

use Core\Database\Migration;
use Core\Database\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eav_api_tokens', function ($table) {
            $table->id('token_id');
            $table->integer('user_id')->unsigned();
            $table->string('token_hash', 255)->unique();
            $table->string('token_name', 100);
            $table->json('scopes');
            $table->datetime('expires_at')->nullable();
            $table->datetime('last_used_at')->nullable();
            $table->datetime('created_at');
            $table->tinyInteger('is_active')->default(1);
            
            // Indexes
            $table->index('user_id');
            $table->index('token_hash');
            $table->index('expires_at');
            $table->index('is_active');
            
            // Foreign keys
            $table->foreignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eav_api_tokens');
    }
};
