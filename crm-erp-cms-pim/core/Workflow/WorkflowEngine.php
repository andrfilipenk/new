<?php

namespace Core\Workflow;

use Core\Database\DB;

/**
 * Generic Workflow Engine
 * 
 * Provides configurable multi-step approval workflows for any entity
 */
class WorkflowEngine
{
    /**
     * Create a workflow definition
     */
    public static function createWorkflow(array $data): int
    {
        return DB::table('workflows')->insert([
            'name' => $data['name'],
            'entity_type' => $data['entity_type'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
    
    /**
     * Add step to workflow
     */
    public static function addStep(int $workflowId, array $stepData): int
    {
        return DB::table('workflow_steps')->insert([
            'workflow_id' => $workflowId,
            'step_name' => $stepData['name'],
            'step_order' => $stepData['order'],
            'step_type' => $stepData['type'] ?? 'approval', // approval, notification, automation
            'required_role_id' => $stepData['required_role_id'] ?? null,
            'required_permission' => $stepData['required_permission'] ?? null,
            'can_reject' => $stepData['can_reject'] ?? 1,
            'auto_approve_after_hours' => $stepData['auto_approve_after_hours'] ?? null,
            'parallel_approval' => $stepData['parallel_approval'] ?? 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
    
    /**
     * Start workflow instance for an entity
     */
    public static function startWorkflow(int $workflowId, string $entityType, int $entityId, int $initiatedBy): int
    {
        // Create workflow instance
        $instanceId = DB::table('workflow_instances')->insert([
            'workflow_id' => $workflowId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'status' => 'in_progress',
            'current_step' => 1,
            'initiated_by' => $initiatedBy,
            'initiated_at' => date('Y-m-d H:i:s'),
        ]);
        
        // Get first step
        $firstStep = DB::table('workflow_steps')
            ->where('workflow_id', '=', $workflowId)
            ->where('step_order', '=', 1)
            ->first();
        
        if ($firstStep) {
            // Create step instance
            self::createStepInstance($instanceId, $firstStep['id']);
            
            // Send notifications
            self::notifyApprovers($instanceId, $firstStep['id']);
        }
        
        return $instanceId;
    }
    
    /**
     * Create step instance
     */
    private static function createStepInstance(int $instanceId, int $stepId): int
    {
        return DB::table('workflow_step_instances')->insert([
            'workflow_instance_id' => $instanceId,
            'workflow_step_id' => $stepId,
            'status' => 'pending',
            'started_at' => date('Y-m-d H:i:s'),
        ]);
    }
    
    /**
     * Approve step
     */
    public static function approve(int $instanceId, int $userId, ?string $comments = null): bool
    {
        $instance = DB::table('workflow_instances')->where('id', '=', $instanceId)->first();
        
        if (!$instance || $instance['status'] !== 'in_progress') {
            return false;
        }
        
        // Get current step instance
        $stepInstance = DB::table('workflow_step_instances')
            ->where('workflow_instance_id', '=', $instanceId)
            ->where('status', '=', 'pending')
            ->orderBy('id', 'DESC')
            ->first();
        
        if (!$stepInstance) {
            return false;
        }
        
        // Check if user has permission to approve
        if (!self::canUserApprove($userId, $stepInstance['workflow_step_id'])) {
            return false;
        }
        
        // Record approval
        DB::table('workflow_approvals')->insert([
            'workflow_instance_id' => $instanceId,
            'workflow_step_id' => $stepInstance['workflow_step_id'],
            'user_id' => $userId,
            'action' => 'approved',
            'comments' => $comments,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        
        // Update step instance
        DB::table('workflow_step_instances')
            ->where('id', '=', $stepInstance['id'])
            ->update([
                'status' => 'approved',
                'approved_by' => $userId,
                'approved_at' => date('Y-m-d H:i:s'),
            ]);
        
        // Move to next step or complete
        return self::moveToNextStep($instanceId);
    }
    
    /**
     * Reject step
     */
    public static function reject(int $instanceId, int $userId, string $reason): bool
    {
        $instance = DB::table('workflow_instances')->where('id', '=', $instanceId)->first();
        
        if (!$instance || $instance['status'] !== 'in_progress') {
            return false;
        }
        
        // Get current step instance
        $stepInstance = DB::table('workflow_step_instances')
            ->where('workflow_instance_id', '=', $instanceId)
            ->where('status', '=', 'pending')
            ->orderBy('id', 'DESC')
            ->first();
        
        if (!$stepInstance) {
            return false;
        }
        
        // Check if user has permission
        if (!self::canUserApprove($userId, $stepInstance['workflow_step_id'])) {
            return false;
        }
        
        // Record rejection
        DB::table('workflow_approvals')->insert([
            'workflow_instance_id' => $instanceId,
            'workflow_step_id' => $stepInstance['workflow_step_id'],
            'user_id' => $userId,
            'action' => 'rejected',
            'comments' => $reason,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        
        // Update step instance
        DB::table('workflow_step_instances')
            ->where('id', '=', $stepInstance['id'])
            ->update([
                'status' => 'rejected',
                'approved_by' => $userId,
                'approved_at' => date('Y-m-d H:i:s'),
            ]);
        
        // Update workflow instance
        DB::table('workflow_instances')
            ->where('id', '=', $instanceId)
            ->update([
                'status' => 'rejected',
                'completed_at' => date('Y-m-d H:i:s'),
            ]);
        
        // Notify initiator
        self::notifyRejection($instanceId, $reason);
        
        return true;
    }
    
    /**
     * Move to next step
     */
    private static function moveToNextStep(int $instanceId): bool
    {
        $instance = DB::table('workflow_instances')->where('id', '=', $instanceId)->first();
        
        // Get next step
        $nextStep = DB::table('workflow_steps')
            ->where('workflow_id', '=', $instance['workflow_id'])
            ->where('step_order', '=', $instance['current_step'] + 1)
            ->first();
        
        if ($nextStep) {
            // Create next step instance
            self::createStepInstance($instanceId, $nextStep['id']);
            
            // Update workflow instance
            DB::table('workflow_instances')
                ->where('id', '=', $instanceId)
                ->update(['current_step' => $nextStep['step_order']]);
            
            // Notify approvers
            self::notifyApprovers($instanceId, $nextStep['id']);
            
            return true;
        } else {
            // No more steps - complete workflow
            DB::table('workflow_instances')
                ->where('id', '=', $instanceId)
                ->update([
                    'status' => 'completed',
                    'completed_at' => date('Y-m-d H:i:s'),
                ]);
            
            // Notify completion
            self::notifyCompletion($instanceId);
            
            return true;
        }
    }
    
    /**
     * Check if user can approve step
     */
    private static function canUserApprove(int $userId, int $stepId): bool
    {
        $step = DB::table('workflow_steps')->where('id', '=', $stepId)->first();
        
        if (!$step) {
            return false;
        }
        
        // If role required, check user has role
        if ($step['required_role_id']) {
            $hasRole = DB::table('user_roles')
                ->where('user_id', '=', $userId)
                ->where('role_id', '=', $step['required_role_id'])
                ->exists();
            
            if (!$hasRole) {
                return false;
            }
        }
        
        // If permission required, check user has permission
        if ($step['required_permission']) {
            // Check via role permissions
            $hasPermission = DB::table('user_roles')
                ->join('role_permissions', 'user_roles.role_id', '=', 'role_permissions.role_id')
                ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
                ->where('user_roles.user_id', '=', $userId)
                ->where('permissions.name', '=', $step['required_permission'])
                ->exists();
            
            if (!$hasPermission) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get workflow instance details
     */
    public static function getInstanceDetails(int $instanceId): array
    {
        $instance = DB::table('workflow_instances')->where('id', '=', $instanceId)->first();
        
        if (!$instance) {
            return [];
        }
        
        // Get workflow
        $workflow = DB::table('workflows')->where('id', '=', $instance['workflow_id'])->first();
        
        // Get all steps
        $steps = DB::table('workflow_steps')
            ->where('workflow_id', '=', $instance['workflow_id'])
            ->orderBy('step_order', 'ASC')
            ->get();
        
        // Get step instances with approvals
        $stepInstances = [];
        foreach ($steps as $step) {
            $stepInstance = DB::table('workflow_step_instances')
                ->where('workflow_instance_id', '=', $instanceId)
                ->where('workflow_step_id', '=', $step['id'])
                ->first();
            
            $approvals = [];
            if ($stepInstance) {
                $approvals = DB::table('workflow_approvals')
                    ->where('workflow_instance_id', '=', $instanceId)
                    ->where('workflow_step_id', '=', $step['id'])
                    ->get();
            }
            
            $stepInstances[] = [
                'step' => $step,
                'instance' => $stepInstance,
                'approvals' => $approvals,
            ];
        }
        
        return [
            'instance' => $instance,
            'workflow' => $workflow,
            'steps' => $stepInstances,
        ];
    }
    
    /**
     * Get pending approvals for user
     */
    public static function getPendingApprovals(int $userId): array
    {
        // Get user's roles
        $roleIds = DB::table('user_roles')
            ->where('user_id', '=', $userId)
            ->select('role_id')
            ->get();
        
        $roleIds = array_column($roleIds, 'role_id');
        
        if (empty($roleIds)) {
            return [];
        }
        
        // Get pending step instances that user can approve
        $pendingSteps = DB::table('workflow_step_instances as wsi')
            ->join('workflow_instances as wi', 'wsi.workflow_instance_id', '=', 'wi.id')
            ->join('workflow_steps as ws', 'wsi.workflow_step_id', '=', 'ws.id')
            ->join('workflows as w', 'wi.workflow_id', '=', 'w.id')
            ->where('wsi.status', '=', 'pending')
            ->where('wi.status', '=', 'in_progress')
            ->whereIn('ws.required_role_id', $roleIds)
            ->select(
                'wsi.*',
                'wi.entity_type',
                'wi.entity_id',
                'wi.initiated_by',
                'wi.initiated_at',
                'ws.step_name',
                'w.name as workflow_name'
            )
            ->get();
        
        return $pendingSteps;
    }
    
    /**
     * Cancel workflow
     */
    public static function cancel(int $instanceId, int $userId, string $reason): bool
    {
        DB::table('workflow_instances')
            ->where('id', '=', $instanceId)
            ->update([
                'status' => 'cancelled',
                'completed_at' => date('Y-m-d H:i:s'),
            ]);
        
        // Record cancellation
        DB::table('workflow_approvals')->insert([
            'workflow_instance_id' => $instanceId,
            'workflow_step_id' => null,
            'user_id' => $userId,
            'action' => 'cancelled',
            'comments' => $reason,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        
        return true;
    }
    
    /**
     * Generate HTML report for workflow instance
     */
    public static function generateHTMLReport(int $instanceId): string
    {
        $details = self::getInstanceDetails($instanceId);
        
        if (empty($details)) {
            return '<p>Workflow not found</p>';
        }
        
        $html = '<div class="workflow-report">';
        $html .= '<h2>Workflow Report: ' . htmlspecialchars($details['workflow']['name']) . '</h2>';
        
        // Instance info
        $html .= '<table class="table table-bordered">';
        $html .= '<tr><th>Workflow ID</th><td>' . $details['instance']['id'] . '</td></tr>';
        $html .= '<tr><th>Entity Type</th><td>' . htmlspecialchars($details['instance']['entity_type']) . '</td></tr>';
        $html .= '<tr><th>Entity ID</th><td>' . $details['instance']['entity_id'] . '</td></tr>';
        $html .= '<tr><th>Status</th><td><span class="badge bg-' . self::getStatusColor($details['instance']['status']) . '">' . ucfirst($details['instance']['status']) . '</span></td></tr>';
        $html .= '<tr><th>Initiated At</th><td>' . $details['instance']['initiated_at'] . '</td></tr>';
        $html .= '<tr><th>Completed At</th><td>' . ($details['instance']['completed_at'] ?? 'In Progress') . '</td></tr>';
        $html .= '</table>';
        
        // Steps progress
        $html .= '<h3>Workflow Steps</h3>';
        $html .= '<table class="table table-striped">';
        $html .= '<thead><tr><th>Step</th><th>Name</th><th>Status</th><th>Approver</th><th>Date</th><th>Comments</th></tr></thead>';
        $html .= '<tbody>';
        
        foreach ($details['steps'] as $stepData) {
            $step = $stepData['step'];
            $instance = $stepData['instance'];
            $approvals = $stepData['approvals'];
            
            $status = $instance ? $instance['status'] : 'not_started';
            $statusBadge = '<span class="badge bg-' . self::getStatusColor($status) . '">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
            
            $approver = '-';
            $date = '-';
            $comments = '-';
            
            if (!empty($approvals)) {
                $approval = $approvals[0];
                $approver = 'User #' . $approval['user_id'];
                $date = $approval['created_at'];
                $comments = htmlspecialchars($approval['comments'] ?? '-');
            }
            
            $html .= '<tr>';
            $html .= '<td>' . $step['step_order'] . '</td>';
            $html .= '<td>' . htmlspecialchars($step['step_name']) . '</td>';
            $html .= '<td>' . $statusBadge . '</td>';
            $html .= '<td>' . $approver . '</td>';
            $html .= '<td>' . $date . '</td>';
            $html .= '<td>' . $comments . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get status color for badges
     */
    private static function getStatusColor(string $status): string
    {
        return match($status) {
            'completed' => 'success',
            'approved' => 'success',
            'in_progress' => 'primary',
            'pending' => 'warning',
            'rejected' => 'danger',
            'cancelled' => 'secondary',
            default => 'secondary',
        };
    }
    
    /**
     * Notify approvers (placeholder - integrate with NotificationService)
     */
    private static function notifyApprovers(int $instanceId, int $stepId): void
    {
        // Get users who can approve this step
        $step = DB::table('workflow_steps')->where('id', '=', $stepId)->first();
        
        if ($step['required_role_id']) {
            $users = DB::table('user_roles')
                ->where('role_id', '=', $step['required_role_id'])
                ->select('user_id')
                ->get();
            
            foreach ($users as $user) {
                // Send notification via NotificationService
                \App\Services\NotificationService::send(
                    $user['user_id'],
                    'Workflow Approval Required',
                    "You have a pending approval for workflow instance #{$instanceId}",
                    'info'
                );
            }
        }
    }
    
    /**
     * Notify rejection
     */
    private static function notifyRejection(int $instanceId, string $reason): void
    {
        $instance = DB::table('workflow_instances')->where('id', '=', $instanceId)->first();
        
        \App\Services\NotificationService::send(
            $instance['initiated_by'],
            'Workflow Rejected',
            "Your workflow request has been rejected. Reason: {$reason}",
            'warning'
        );
    }
    
    /**
     * Notify completion
     */
    private static function notifyCompletion(int $instanceId): void
    {
        $instance = DB::table('workflow_instances')->where('id', '=', $instanceId)->first();
        
        \App\Services\NotificationService::send(
            $instance['initiated_by'],
            'Workflow Completed',
            "Your workflow request has been successfully completed.",
            'success'
        );
    }
}
