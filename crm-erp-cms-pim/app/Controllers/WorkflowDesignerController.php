<?php

namespace App\Controllers;

use Core\Request;
use Core\Response;
use Core\Database\DB;
use Core\Logger\Logger;

class WorkflowDesignerController
{
    /**
     * Show the workflow designer interface
     */
    public function index(Request $request): Response
    {
        // Get all available entity types from workflows
        $entityTypes = DB::table('workflows')
            ->select('entity_type')
            ->distinct()
            ->get();

        // Get all roles for step assignment
        $roles = DB::table('roles')
            ->select('id', 'name')
            ->get();

        return view('workflow/designer', [
            'title' => 'Visual Workflow Designer',
            'entityTypes' => $entityTypes,
            'roles' => $roles
        ]);
    }

    /**
     * Get workflow definition for visual editing
     */
    public function getWorkflow(Request $request, int $workflowId): Response
    {
        try {
            // Get workflow with steps
            $workflow = DB::table('workflows')
                ->where('id', $workflowId)
                ->first();

            if (!$workflow) {
                return Response::json([
                    'success' => false,
                    'message' => 'Workflow not found'
                ], 404);
            }

            // Get workflow steps
            $steps = DB::table('workflow_steps')
                ->where('workflow_id', $workflowId)
                ->orderBy('step_order', 'ASC')
                ->get();

            // Get role names for steps
            foreach ($steps as $step) {
                if ($step->required_role_id) {
                    $role = DB::table('roles')
                        ->where('id', $step->required_role_id)
                        ->first();
                    $step->role_name = $role ? $role->name : null;
                }
            }

            return Response::json([
                'success' => true,
                'workflow' => $workflow,
                'steps' => $steps
            ]);

        } catch (\Exception $e) {
            Logger::error('Error loading workflow for designer: ' . $e->getMessage());
            return Response::json([
                'success' => false,
                'message' => 'Error loading workflow: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save workflow from visual designer
     */
    public function saveWorkflow(Request $request): Response
    {
        try {
            $data = $request->all();

            // Validate required fields
            if (empty($data['name']) || empty($data['entity_type'])) {
                return Response::json([
                    'success' => false,
                    'message' => 'Workflow name and entity type are required'
                ], 400);
            }

            DB::beginTransaction();

            // Create or update workflow
            if (!empty($data['id'])) {
                // Update existing workflow
                DB::table('workflows')
                    ->where('id', $data['id'])
                    ->update([
                        'name' => $data['name'],
                        'description' => $data['description'] ?? null,
                        'entity_type' => $data['entity_type'],
                        'is_active' => $data['is_active'] ?? true,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                $workflowId = $data['id'];

                // Delete existing steps
                DB::table('workflow_steps')
                    ->where('workflow_id', $workflowId)
                    ->delete();

            } else {
                // Create new workflow
                $workflowId = DB::table('workflows')->insert([
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'entity_type' => $data['entity_type'],
                    'is_active' => $data['is_active'] ?? true,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }

            // Insert workflow steps
            if (!empty($data['steps']) && is_array($data['steps'])) {
                foreach ($data['steps'] as $index => $step) {
                    DB::table('workflow_steps')->insert([
                        'workflow_id' => $workflowId,
                        'step_name' => $step['step_name'],
                        'step_order' => $index + 1,
                        'step_type' => $step['step_type'],
                        'required_role_id' => $step['required_role_id'] ?? null,
                        'auto_approve' => $step['auto_approve'] ?? false,
                        'notification_template' => $step['notification_template'] ?? null,
                        'action_config' => !empty($step['action_config']) ? json_encode($step['action_config']) : null,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }

            DB::commit();

            return Response::json([
                'success' => true,
                'message' => 'Workflow saved successfully',
                'workflow_id' => $workflowId
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Logger::error('Error saving workflow from designer: ' . $e->getMessage());
            return Response::json([
                'success' => false,
                'message' => 'Error saving workflow: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete workflow
     */
    public function deleteWorkflow(Request $request, int $workflowId): Response
    {
        try {
            DB::beginTransaction();

            // Check if workflow has active instances
            $activeInstances = DB::table('workflow_instances')
                ->where('workflow_id', $workflowId)
                ->whereIn('status', ['pending', 'in_progress'])
                ->count();

            if ($activeInstances > 0) {
                return Response::json([
                    'success' => false,
                    'message' => "Cannot delete workflow with {$activeInstances} active instances"
                ], 400);
            }

            // Delete workflow steps
            DB::table('workflow_steps')
                ->where('workflow_id', $workflowId)
                ->delete();

            // Delete workflow
            DB::table('workflows')
                ->where('id', $workflowId)
                ->delete();

            DB::commit();

            return Response::json([
                'success' => true,
                'message' => 'Workflow deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Logger::error('Error deleting workflow: ' . $e->getMessage());
            return Response::json([
                'success' => false,
                'message' => 'Error deleting workflow: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * List all workflows
     */
    public function listWorkflows(Request $request): Response
    {
        try {
            $workflows = DB::table('workflows')
                ->select('id', 'name', 'entity_type', 'description', 'is_active', 'created_at')
                ->orderBy('name', 'ASC')
                ->get();

            // Add step count for each workflow
            foreach ($workflows as $workflow) {
                $workflow->step_count = DB::table('workflow_steps')
                    ->where('workflow_id', $workflow->id)
                    ->count();
            }

            return Response::json([
                'success' => true,
                'workflows' => $workflows
            ]);

        } catch (\Exception $e) {
            Logger::error('Error listing workflows: ' . $e->getMessage());
            return Response::json([
                'success' => false,
                'message' => 'Error listing workflows: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available step types and configurations
     */
    public function getStepTypes(Request $request): Response
    {
        $stepTypes = [
            [
                'type' => 'approval',
                'name' => 'Approval Step',
                'description' => 'Requires approval from a specific role',
                'icon' => 'check-circle',
                'color' => '#28a745',
                'fields' => [
                    'step_name' => ['type' => 'text', 'required' => true],
                    'required_role_id' => ['type' => 'select', 'required' => true, 'source' => 'roles'],
                    'auto_approve' => ['type' => 'checkbox', 'required' => false]
                ]
            ],
            [
                'type' => 'notification',
                'name' => 'Notification Step',
                'description' => 'Send notification to users/roles',
                'icon' => 'bell',
                'color' => '#007bff',
                'fields' => [
                    'step_name' => ['type' => 'text', 'required' => true],
                    'required_role_id' => ['type' => 'select', 'required' => false, 'source' => 'roles'],
                    'notification_template' => ['type' => 'textarea', 'required' => false]
                ]
            ],
            [
                'type' => 'automation',
                'name' => 'Automation Step',
                'description' => 'Execute automated action',
                'icon' => 'cog',
                'color' => '#ffc107',
                'fields' => [
                    'step_name' => ['type' => 'text', 'required' => true],
                    'action_config' => ['type' => 'json', 'required' => false]
                ]
            ],
            [
                'type' => 'conditional',
                'name' => 'Conditional Step',
                'description' => 'Branch based on conditions',
                'icon' => 'code-branch',
                'color' => '#6f42c1',
                'fields' => [
                    'step_name' => ['type' => 'text', 'required' => true],
                    'action_config' => ['type' => 'json', 'required' => true]
                ]
            ],
            [
                'type' => 'parallel',
                'name' => 'Parallel Step',
                'description' => 'Execute multiple approvals in parallel',
                'icon' => 'stream',
                'color' => '#17a2b8',
                'fields' => [
                    'step_name' => ['type' => 'text', 'required' => true],
                    'required_role_id' => ['type' => 'multiselect', 'required' => true, 'source' => 'roles']
                ]
            ]
        ];

        return Response::json([
            'success' => true,
            'step_types' => $stepTypes
        ]);
    }

    /**
     * Validate workflow configuration
     */
    public function validateWorkflow(Request $request): Response
    {
        try {
            $data = $request->all();
            $errors = [];

            // Validate workflow name
            if (empty($data['name'])) {
                $errors[] = 'Workflow name is required';
            }

            // Validate entity type
            if (empty($data['entity_type'])) {
                $errors[] = 'Entity type is required';
            }

            // Validate steps
            if (empty($data['steps']) || !is_array($data['steps'])) {
                $errors[] = 'Workflow must have at least one step';
            } else {
                foreach ($data['steps'] as $index => $step) {
                    if (empty($step['step_name'])) {
                        $errors[] = "Step " . ($index + 1) . ": Step name is required";
                    }
                    if (empty($step['step_type'])) {
                        $errors[] = "Step " . ($index + 1) . ": Step type is required";
                    }
                    if ($step['step_type'] === 'approval' && empty($step['required_role_id'])) {
                        $errors[] = "Step " . ($index + 1) . ": Approval steps require a role";
                    }
                }
            }

            return Response::json([
                'success' => empty($errors),
                'valid' => empty($errors),
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            Logger::error('Error validating workflow: ' . $e->getMessage());
            return Response::json([
                'success' => false,
                'message' => 'Error validating workflow: ' . $e->getMessage()
            ], 500);
        }
    }
}
