<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create CRM Accounts Table
 */
class CreateCrmAccountsTable extends Migration
{
    public function up(): void
    {
        $this->createTable('crm_accounts', function(Schema $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 50)->comment('Type: prospect, customer, partner, vendor');
            $table->string('industry', 100)->nullable();
            $table->string('website')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->decimal('annual_revenue', 15, 2)->nullable();
            $table->integer('employees')->nullable();
            $table->foreignId('owner_id')->comment('User ID');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('owner_id')->references('id')->on('users');
            $table->index(['type']);
            $table->index(['owner_id']);
            $table->index(['name']);
        });
    }
    
    public function down(): void
    {
        $this->dropTable('crm_accounts');
    }
}
