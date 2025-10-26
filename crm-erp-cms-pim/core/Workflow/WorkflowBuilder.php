<?php

namespace Core\Workflow;

use Core\Database\DB;

/**
 * Workflow Builder
 * 
 * Fluent interface for building workflows
 */
class WorkflowBuilder
{
    private int $workflowId;
    private array $steps = [];
    
    /**
     * Create new workflow
     */
    public static function create(string $name, string $entityType, ?string $description = null): self
    {
        $builder = new self();
        
        $builder->workflowId = WorkflowEngine::createWorkflow([
            'name' => $name,
            'entity_type' => $entityType,
            'description' => $description,
            'is_active' => 1,
        ]);
        
        return $builder;
    }
    
    /**
     * Add approval step
     */
    public function addApprovalStep(
        string $name,
        int $order,
        ?int $requiredRoleId = null,
        ?string $requiredPermission = null,
        bool $canReject = true
    ): self {
        WorkflowEngine::addStep($this->workflowId, [
            'name' => $name,
            'order' => $order,
            'type' => 'approval',
            'required_role_id' => $requiredRoleId,
            'required_permission' => $requiredPermission,
            'can_reject' => $canReject,
        ]);
        
        $this->steps[] = $name;
        
        return $this;
    }
    
    /**
     * Add notification step
     */
    public function addNotificationStep(string $name, int $order): self
    {
        WorkflowEngine::addStep($this->workflowId, [
            'name' => $name,
            'order' => $order,
            'type' => 'notification',
        ]);
        
        $this->steps[] = $name;
        
        return $this;
    }
    
    /**
     * Add automation step
     */
    public function addAutomationStep(string $name, int $order): self
    {
        WorkflowEngine::addStep($this->workflowId, [
            'name' => $name,
            'order' => $order,
            'type' => 'automation',
        ]);
        
        $this->steps[] = $name;
        
        return $this;
    }
    
    /**
     * Get workflow ID
     */
    public function getWorkflowId(): int
    {
        return $this->workflowId;
    }
    
    /**
     * Get steps count
     */
    public function getStepsCount(): int
    {
        return count($this->steps);
    }
}

/**
 * Example Usage:
 * 
 * // Create a purchase order approval workflow
 * $workflow = WorkflowBuilder::create(
 *     'Purchase Order Approval',
 *     'PurchaseOrder',
 *     'Multi-level approval for purchase orders'
 * )
 * ->addApprovalStep('Manager Approval', 1, $managerRoleId)
 * ->addApprovalStep('Finance Approval', 2, $financeRoleId)
 * ->addApprovalStep('Director Approval', 3, $directorRoleId)
 * ->addNotificationStep('Notify Procurement', 4);
 * 
 * // Start workflow for a purchase order
 * $instanceId = WorkflowEngine::startWorkflow(
 *     $workflow->getWorkflowId(),
 *     'PurchaseOrder',
 *     $purchaseOrderId,
 *     $currentUserId
 * );
 * 
 * // Approve step
 * WorkflowEngine::approve($instanceId, $approverId, 'Approved - within budget');
 * 
 * // Reject step
 * WorkflowEngine::reject($instanceId, $approverId, 'Amount exceeds budget limit');
 * 
 * // Get pending approvals for user
 * $pending = WorkflowEngine::getPendingApprovals($userId);
 * 
 * // Generate HTML report
 * $html = WorkflowEngine::generateHTMLReport($instanceId);
 */
