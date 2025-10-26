<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create CRM Leads Table
 */
class CreateCrmLeadsTable extends Migration
{
    public function up(): void
    {
        $this->createTable('crm_leads', function(Schema $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('company');
            $table->string('title', 100)->nullable();
            $table->string('email');
            $table->string('phone', 50)->nullable();
            $table->string('source', 50)->comment('Source: website, referral, campaign, etc.');
            $table->enum('status', ['new', 'contacted', 'qualified', 'unqualified', 'converted'])->default('new');
            $table->integer('score')->default(0)->comment('Lead score');
            $table->foreignId('owner_id')->comment('User ID');
            $table->text('notes')->nullable();
            $table->foreignId('converted_account_id')->nullable();
            $table->foreignId('converted_contact_id')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('owner_id')->references('id')->on('users');
            $table->foreign('converted_account_id')->references('id')->on('crm_accounts')->nullOnDelete();
            $table->foreign('converted_contact_id')->references('id')->on('crm_contacts')->nullOnDelete();
            $table->index(['status']);
            $table->index(['source']);
            $table->index(['owner_id']);
            $table->index(['email']);
        });
    }
    
    public function down(): void
    {
        $this->dropTable('crm_leads');
    }
}
