<?php

use Core\Database\Schema;
use Core\Database\Migration;

/**
 * Create Workflow Tables
 */
class CreateWorkflowTables extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        // Workflow definitions
        $this->createTable('workflows', function(Schema $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('entity_type', 100); // e.g., 'PurchaseOrder', 'Leave', 'Expense'
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });
        
        // Workflow steps
        $this->createTable('workflow_steps', function(Schema $table) {
            $table->id();
            $table->foreignId('workflow_id');
            $table->string('step_name', 200);
            $table->integer('step_order');
            $table->string('step_type', 50)->default('approval'); // approval, notification, automation, conditional, parallel
            $table->foreignId('required_role_id')->nullable();
            $table->string('required_permission', 100)->nullable();
            $table->boolean('can_reject')->default(1);
            $table->boolean('auto_approve')->default(0);
            $table->integer('auto_approve_after_hours')->nullable();
            $table->boolean('parallel_approval')->default(0); // Multiple approvers required
            $table->text('notification_template')->nullable(); // Template for notification steps
            $table->json('action_config')->nullable(); // JSON config for automation/conditional/parallel steps
            $table->timestamps();
            
            $table->index(['workflow_id']);
            $table->index(['workflow_id', 'step_order']);
        });
        
        // Workflow instances
        $this->createTable('workflow_instances', function(Schema $table) {
            $table->id();
            $table->foreignId('workflow_id');
            $table->string('entity_type', 100);
            $table->integer('entity_id');
            $table->string('status', 50)->default('in_progress'); // in_progress, completed, rejected, cancelled
            $table->integer('current_step')->default(1);
            $table->foreignId('initiated_by');
            $table->timestamp('initiated_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['entity_type', 'entity_id']);
            $table->index(['workflow_id']);
            $table->index(['status']);
        });
        
        // Workflow step instances
        $this->createTable('workflow_step_instances', function(Schema $table) {
            $table->id();
            $table->foreignId('workflow_instance_id');
            $table->foreignId('workflow_step_id');
            $table->string('status', 50)->default('pending'); // pending, approved, rejected, skipped
            $table->foreignId('approved_by')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            
            $table->index(['workflow_instance_id']);
            $table->index(['status']);
        });
        
        // Workflow approvals (history)
        $this->createTable('workflow_approvals', function(Schema $table) {
            $table->id();
            $table->foreignId('workflow_instance_id');
            $table->foreignId('workflow_step_id')->nullable();
            $table->foreignId('user_id');
            $table->string('action', 50); // approved, rejected, cancelled
            $table->text('comments')->nullable();
            $table->timestamps();
            
            $table->index(['workflow_instance_id']);
            $table->index(['user_id']);
        });
    }
    
    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->dropTable('workflow_approvals');
        $this->dropTable('workflow_step_instances');
        $this->dropTable('workflow_instances');
        $this->dropTable('workflow_steps');
        $this->dropTable('workflows');
    }
}
