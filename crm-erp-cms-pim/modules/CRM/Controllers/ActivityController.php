<?php

namespace Modules\CRM\Controllers;

use Core\Request;
use Core\Response;
use Modules\CRM\Models\Activity;
use App\Services\AuditService;
use App\Services\NotificationService;
use Core\Validation\Validator;

/**
 * Activity Controller
 * 
 * Handles HTTP requests for activity/task management with polymorphic relations
 */
class ActivityController
{
    /**
     * Display list of activities
     */
    public function index(Request $request): Response
    {
        $relatedType = $request->get('related_type');
        $relatedId = $request->get('related_id');
        $status = $request->get('status');
        
        $query = \Core\Database\DB::table('crm_activities');
        
        if ($relatedType && $relatedId) {
            $query->where('related_to_type', '=', $relatedType)
                  ->where('related_to_id', '=', $relatedId);
        }
        
        if ($status) {
            $query->where('status', '=', $status);
        }
        
        $activities = $query->orderBy('due_date', 'ASC')->get();
        $activities = array_map(fn($row) => Activity::hydrate($row), $activities);
        
        if ($request->expectsJson()) {
            return Response::json(['activities' => $activities]);
        }
        
        return view('crm/activities/index', [
            'activities' => $activities,
            'relatedType' => $relatedType,
            'relatedId' => $relatedId,
            'status' => $status,
        ]);
    }
    
    /**
     * Show create form
     */
    public function create(Request $request): Response
    {
        $relatedType = $request->get('related_type');
        $relatedId = $request->get('related_id');
        
        return view('crm/activities/create', [
            'relatedType' => $relatedType,
            'relatedId' => $relatedId,
        ]);
    }
    
    /**
     * Store new activity
     */
    public function store(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:call,meeting,email,task,note,other',
            'subject' => 'required|max:200',
            'description' => 'max:1000',
            'due_date' => 'date',
            'status' => 'required|in:planned,in_progress,completed,cancelled',
            'priority' => 'required|in:low,normal,high,urgent',
            'related_to_type' => 'required|in:account,contact,lead,opportunity',
            'related_to_id' => 'required|integer',
            'assigned_to_id' => 'integer',
        ]);
        
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return Response::json(['errors' => $validator->errors()], 422);
            }
            return redirect('/crm/activities/create')->with('errors', $validator->errors());
        }
        
        try {
            $data = $validator->validated();
            $data['created_by_id'] = auth()->id();
            
            $activity = Activity::create($data);
            
            // Log audit trail
            AuditService::logCreate('Activity', $activity->id, $data, auth()->id());
            
            // Notify assigned user
            if (isset($data['assigned_to_id']) && $data['assigned_to_id']) {
                NotificationService::send(
                    $data['assigned_to_id'],
                    'New Activity Assigned',
                    "A new {$data['type']} activity '{$data['subject']}' has been assigned to you.",
                    'info'
                );
            }
            
            if ($request->expectsJson()) {
                return Response::json(['activity' => $activity, 'message' => 'Activity created successfully'], 201);
            }
            
            // Redirect back to related entity
            $redirectUrl = $this->getRedirectUrl($data['related_to_type'], $data['related_to_id']);
            return redirect($redirectUrl)->with('success', 'Activity created successfully');
        } catch (\Exception $e) {
            logger()->error('Activity creation failed', ['error' => $e->getMessage()]);
            
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Failed to create activity'], 500);
            }
            
            return redirect('/crm/activities/create')->with('error', 'Failed to create activity');
        }
    }
    
    /**
     * Show single activity
     */
    public function show(Request $request, int $id): Response
    {
        $activity = Activity::findOrFail($id);
        
        // Load relationships
        $relatedEntity = $activity->relatedTo();
        $assignedTo = $activity->assignedTo();
        $createdBy = $activity->createdBy();
        
        if ($request->expectsJson()) {
            return Response::json([
                'activity' => $activity,
                'related_entity' => $relatedEntity,
                'assigned_to' => $assignedTo,
                'created_by' => $createdBy,
            ]);
        }
        
        return view('crm/activities/show', [
            'activity' => $activity,
            'relatedEntity' => $relatedEntity,
            'assignedTo' => $assignedTo,
            'createdBy' => $createdBy,
        ]);
    }
    
    /**
     * Show edit form
     */
    public function edit(int $id): Response
    {
        $activity = Activity::findOrFail($id);
        
        return view('crm/activities/edit', ['activity' => $activity]);
    }
    
    /**
     * Update activity
     */
    public function update(Request $request, int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:call,meeting,email,task,note,other',
            'subject' => 'required|max:200',
            'description' => 'max:1000',
            'due_date' => 'date',
            'status' => 'required|in:planned,in_progress,completed,cancelled',
            'priority' => 'required|in:low,normal,high,urgent',
            'related_to_type' => 'required|in:account,contact,lead,opportunity',
            'related_to_id' => 'required|integer',
            'assigned_to_id' => 'integer',
        ]);
        
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return Response::json(['errors' => $validator->errors()], 422);
            }
            return redirect("/crm/activities/{$id}/edit")->with('errors', $validator->errors());
        }
        
        try {
            $activity = Activity::findOrFail($id);
            $oldData = $activity->toArray();
            
            $data = $validator->validated();
            $activity->update($data);
            
            // Log audit trail
            AuditService::logUpdate('Activity', $id, $oldData, $data, auth()->id());
            
            // Notify if assigned user changed
            if (isset($data['assigned_to_id']) && $data['assigned_to_id'] != $oldData['assigned_to_id']) {
                NotificationService::send(
                    $data['assigned_to_id'],
                    'Activity Reassigned',
                    "Activity '{$data['subject']}' has been assigned to you.",
                    'info'
                );
            }
            
            if ($request->expectsJson()) {
                return Response::json(['activity' => $activity, 'message' => 'Activity updated successfully']);
            }
            
            $redirectUrl = $this->getRedirectUrl($data['related_to_type'], $data['related_to_id']);
            return redirect($redirectUrl)->with('success', 'Activity updated successfully');
        } catch (\Exception $e) {
            logger()->error('Activity update failed', ['error' => $e->getMessage(), 'activity_id' => $id]);
            
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Failed to update activity'], 500);
            }
            
            return redirect("/crm/activities/{$id}/edit")->with('error', 'Failed to update activity');
        }
    }
    
    /**
     * Delete activity
     */
    public function destroy(Request $request, int $id): Response
    {
        try {
            $activity = Activity::findOrFail($id);
            $relatedType = $activity->related_to_type;
            $relatedId = $activity->related_to_id;
            
            $activity->delete();
            
            if ($request->expectsJson()) {
                return Response::json(['message' => 'Activity deleted successfully']);
            }
            
            $redirectUrl = $this->getRedirectUrl($relatedType, $relatedId);
            return redirect($redirectUrl)->with('success', 'Activity deleted successfully');
        } catch (\Exception $e) {
            logger()->error('Activity deletion failed', ['error' => $e->getMessage(), 'activity_id' => $id]);
            
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Failed to delete activity'], 500);
            }
            
            return redirect('/crm/activities')->with('error', 'Failed to delete activity');
        }
    }
    
    /**
     * Mark activity as completed
     */
    public function markCompleted(Request $request, int $id): Response
    {
        try {
            $activity = Activity::findOrFail($id);
            $activity->markCompleted();
            
            // Log audit trail
            AuditService::logUpdate('Activity', $id, ['status' => 'planned'], ['status' => 'completed'], auth()->id());
            
            if ($request->expectsJson()) {
                return Response::json(['activity' => $activity, 'message' => 'Activity marked as completed']);
            }
            
            $redirectUrl = $this->getRedirectUrl($activity->related_to_type, $activity->related_to_id);
            return redirect($redirectUrl)->with('success', 'Activity marked as completed');
        } catch (\Exception $e) {
            logger()->error('Activity completion failed', ['error' => $e->getMessage(), 'activity_id' => $id]);
            
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Failed to mark activity as completed'], 500);
            }
            
            return redirect('/crm/activities')->with('error', 'Failed to mark activity as completed');
        }
    }
    
    /**
     * Get upcoming activities
     */
    public function upcoming(Request $request): Response
    {
        $userId = $request->get('user_id', auth()->id());
        $limit = $request->get('limit', 10);
        
        $activities = \Core\Database\DB::table('crm_activities')
            ->where('assigned_to_id', '=', $userId)
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled')
            ->orderBy('due_date', 'ASC')
            ->limit($limit)
            ->get();
        
        $activities = array_map(fn($row) => Activity::hydrate($row), $activities);
        
        return Response::json(['activities' => $activities]);
    }
    
    /**
     * Get overdue activities
     */
    public function overdue(Request $request): Response
    {
        $userId = $request->get('user_id', auth()->id());
        
        $activities = \Core\Database\DB::table('crm_activities')
            ->where('assigned_to_id', '=', $userId)
            ->where('status', '=', 'planned')
            ->where('due_date', '<', date('Y-m-d H:i:s'))
            ->orderBy('due_date', 'ASC')
            ->get();
        
        $activities = array_map(fn($row) => Activity::hydrate($row), $activities);
        
        return Response::json(['activities' => $activities]);
    }
    
    /**
     * Get redirect URL based on related entity
     */
    private function getRedirectUrl(string $relatedType, int $relatedId): string
    {
        return match(strtolower($relatedType)) {
            'account' => "/crm/accounts/{$relatedId}",
            'contact' => "/crm/contacts/{$relatedId}",
            'lead' => "/crm/leads/{$relatedId}",
            'opportunity' => "/crm/opportunities/{$relatedId}",
            default => '/crm/activities',
        };
    }
}
