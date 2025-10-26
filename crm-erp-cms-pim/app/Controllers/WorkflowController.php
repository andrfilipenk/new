<?php

namespace App\Controllers;

use Core\Request;
use Core\Response;
use Core\Workflow\WorkflowEngine;
use Core\Workflow\WorkflowBuilder;

/**
 * Workflow Controller
 * 
 * Manages workflow operations via API
 */
class WorkflowController
{
    /**
     * Get pending approvals for current user
     */
    public function getPendingApprovals(Request $request): Response
    {
        $userId = auth()->id();
        
        if (!$userId) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }
        
        $pending = WorkflowEngine::getPendingApprovals($userId);
        
        return Response::json([
            'pending_approvals' => $pending,
            'count' => count($pending)
        ]);
    }
    
    /**
     * Approve workflow step
     */
    public function approve(Request $request, int $instanceId): Response
    {
        $userId = auth()->id();
        
        if (!$userId) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }
        
        $comments = $request->input('comments');
        
        $result = WorkflowEngine::approve($instanceId, $userId, $comments);
        
        if ($result) {
            return Response::json([
                'message' => 'Workflow step approved successfully',
                'instance_id' => $instanceId
            ]);
        }
        
        return Response::json(['error' => 'Failed to approve workflow'], 400);
    }
    
    /**
     * Reject workflow step
     */
    public function reject(Request $request, int $instanceId): Response
    {
        $userId = auth()->id();
        
        if (!$userId) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }
        
        $reason = $request->input('reason');
        
        if (!$reason) {
            return Response::json(['error' => 'Rejection reason is required'], 422);
        }
        
        $result = WorkflowEngine::reject($instanceId, $userId, $reason);
        
        if ($result) {
            return Response::json([
                'message' => 'Workflow rejected',
                'instance_id' => $instanceId
            ]);
        }
        
        return Response::json(['error' => 'Failed to reject workflow'], 400);
    }
    
    /**
     * Get workflow instance details
     */
    public function getInstanceDetails(Request $request, int $instanceId): Response
    {
        $details = WorkflowEngine::getInstanceDetails($instanceId);
        
        if (empty($details)) {
            return Response::json(['error' => 'Workflow instance not found'], 404);
        }
        
        return Response::json($details);
    }
    
    /**
     * Generate HTML report
     */
    public function generateReport(Request $request, int $instanceId): Response
    {
        $html = WorkflowEngine::generateHTMLReport($instanceId);
        
        if ($request->expectsJson()) {
            return Response::json(['html' => $html]);
        }
        
        return Response::html($html);
    }
    
    /**
     * Cancel workflow
     */
    public function cancel(Request $request, int $instanceId): Response
    {
        $userId = auth()->id();
        
        if (!$userId) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }
        
        $reason = $request->input('reason', 'Cancelled by user');
        
        $result = WorkflowEngine::cancel($instanceId, $userId, $reason);
        
        if ($result) {
            return Response::json(['message' => 'Workflow cancelled']);
        }
        
        return Response::json(['error' => 'Failed to cancel workflow'], 400);
    }
    
    /**
     * Create new workflow definition
     */
    public function createWorkflow(Request $request): Response
    {
        $userId = auth()->id();
        
        if (!$userId) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }
        
        $name = $request->input('name');
        $entityType = $request->input('entity_type');
        $description = $request->input('description');
        $steps = $request->input('steps', []);
        
        if (!$name || !$entityType) {
            return Response::json(['error' => 'Name and entity type are required'], 422);
        }
        
        $builder = WorkflowBuilder::create($name, $entityType, $description);
        
        foreach ($steps as $step) {
            $builder->addApprovalStep(
                $step['name'],
                $step['order'],
                $step['required_role_id'] ?? null,
                $step['required_permission'] ?? null,
                $step['can_reject'] ?? true
            );
        }
        
        return Response::json([
            'message' => 'Workflow created successfully',
            'workflow_id' => $builder->getWorkflowId(),
            'steps_count' => $builder->getStepsCount()
        ], 201);
    }
}
