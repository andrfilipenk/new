<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create CRM Contacts Table
 */
class CreateCrmContactsTable extends Migration
{
    public function up(): void
    {
        $this->createTable('crm_contacts', function(Schema $table) {
            $table->id();
            $table->foreignId('account_id')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('title', 100)->nullable();
            $table->string('email');
            $table->string('phone', 50)->nullable();
            $table->string('mobile', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('linkedin')->nullable();
            $table->foreignId('owner_id')->comment('User ID');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('account_id')->references('id')->on('crm_accounts')->nullOnDelete();
            $table->foreign('owner_id')->references('id')->on('users');
            $table->index(['account_id']);
            $table->index(['owner_id']);
            $table->index(['email']);
            $table->index(['last_name']);
        });
    }
    
    public function down(): void
    {
        $this->dropTable('crm_contacts');
    }
}
