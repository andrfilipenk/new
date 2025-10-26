<?php

use Core\Database\Migration;
use Core\Database\Schema;

/**
 * Create CRM Activities Table
 */
class CreateCrmActivitiesTable extends Migration
{
    public function up(): void
    {
        $this->createTable('crm_activities', function(Schema $table) {
            $table->id();
            $table->enum('type', ['call', 'meeting', 'email', 'task', 'note']);
            $table->string('subject');
            $table->text('description')->nullable();
            $table->datetime('due_date')->nullable();
            $table->enum('status', ['planned', 'completed', 'cancelled'])->default('planned');
            $table->enum('priority', ['low', 'normal', 'high'])->default('normal');
            $table->string('related_to_type', 50)->comment('Polymorphic: account, contact, lead, opportunity');
            $table->foreignId('related_to_id');
            $table->foreignId('assigned_to_id')->comment('User ID');
            $table->foreignId('created_by_id')->comment('User ID');
            $table->timestamps();
            
            $table->foreign('assigned_to_id')->references('id')->on('users');
            $table->foreign('created_by_id')->references('id')->on('users');
            $table->index(['related_to_type', 'related_to_id']);
            $table->index(['type']);
            $table->index(['status']);
            $table->index(['assigned_to_id']);
            $table->index(['due_date']);
        });
    }
    
    public function down(): void
    {
        $this->dropTable('crm_activities');
    }
}
