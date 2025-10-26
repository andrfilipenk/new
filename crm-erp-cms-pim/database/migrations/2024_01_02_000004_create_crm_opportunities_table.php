<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create CRM Opportunities Table
 */
class CreateCrmOpportunitiesTable extends Migration
{
    public function up(): void
    {
        $this->createTable('crm_opportunities', function(Schema $table) {
            $table->id();
            $table->foreignId('account_id');
            $table->foreignId('contact_id')->nullable();
            $table->string('name');
            $table->decimal('amount', 15, 2);
            $table->date('close_date');
            $table->enum('stage', ['prospecting', 'qualification', 'proposal', 'negotiation', 'closed_won', 'closed_lost']);
            $table->integer('probability')->default(0)->comment('Win probability 0-100');
            $table->string('type', 50)->nullable()->comment('Type: new_business, existing_business, renewal');
            $table->string('lead_source', 50)->nullable();
            $table->foreignId('owner_id')->comment('User ID');
            $table->text('description')->nullable();
            $table->text('next_steps')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('account_id')->references('id')->on('crm_accounts')->cascadeOnDelete();
            $table->foreign('contact_id')->references('id')->on('crm_contacts')->nullOnDelete();
            $table->foreign('owner_id')->references('id')->on('users');
            $table->index(['account_id']);
            $table->index(['stage']);
            $table->index(['owner_id']);
            $table->index(['close_date']);
        });
    }
    
    public function down(): void
    {
        $this->dropTable('crm_opportunities');
    }
}
