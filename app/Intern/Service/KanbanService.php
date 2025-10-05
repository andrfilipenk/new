<?php
// app/Intern/Service/KanbanService.php
namespace Intern\Service;

use Core\Di\Injectable;
use Core\Events\EventAware;
use Intern\Model\Task;
use Intern\Model\TaskStatus;
use Intern\Model\TaskPriority;
use Intern\Model\TaskLog;
use Admin\Model\User;

class KanbanService
{
    use Injectable, EventAware;

    /**
     * Retrieve board layout with tasks organized by status
     * 
     * @param array $filters
     * @return array
     */
    public function retrieveBoardLayout(array $filters = []): array
    {
        $statuses = TaskStatus::getKanbanStatuses();
        $tasks = [];
        $users = [];
        
        foreach ($statuses as $status) {
            $statusTasks = Task::where('status_id', $status->id)
                ->with(['creator', 'assigned', 'priority'])
                ->orderBy('position', 'asc')
                ->orderBy('id', 'asc');
            
            // Apply filters
            if (!empty($filters['assigned_to'])) {
                $statusTasks->where('assigned_to', $filters['assigned_to']);
            }
            
            if (!empty($filters['priority_id'])) {
                $statusTasks->where('priority_id', $filters['priority_id']);
            }
            
            if (!empty($filters['created_by'])) {
                $statusTasks->where('created_by', $filters['created_by']);
            }
            
            $taskList = $statusTasks->get();
            $tasks[$status->id] = $taskList->map(function($task) use (&$users) {
                // Collect users for frontend
                if ($task->assigned && !isset($users[$task->assigned_to])) {
                    $users[$task->assigned_to] = [
                        'id' => $task->assigned->id,
                        'name' => $task->assigned->name ?? 'Unknown User',
                        'avatar' => $this->getUserAvatar($task->assigned)
                    ];
                }
                
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description ?? '',
                    'priority' => [
                        'id' => $task->priority->id ?? 1,
                        'title' => $task->priority->title ?? 'Unknown',
                        'color' => $task->priority->color ?? 'secondary'
                    ],
                    'assigned' => $task->assigned ? [
                        'id' => $task->assigned->id,
                        'name' => $task->assigned->name ?? 'Unknown User'
                    ] : null,
                    'position' => $task->position,
                    'due_date' => $task->end_date,
                    'created_at' => $task->created_at,
                    'updated_at' => $task->updated_at
                ];
            })->toArray();
        }
        
        return [
            'statuses' => $statuses->map(function($status) {
                return [
                    'id' => $status->id,
                    'title' => $status->title,
                    'code' => $status->code,
                    'color' => $status->getColorClass(),
                    'position' => $status->getDisplayOrder(),
                    'task_count' => $status->getTaskCount()
                ];
            })->toArray(),
            'tasks' => $tasks,
            'users' => array_values($users)
        ];
    }

    /**
     * Validate status transition
     * 
     * @param int $taskId
     * @param int $newStatusId
     * @return bool
     */
    public function validateStatusTransition(int $taskId, int $newStatusId): bool
    {
        $task = Task::find($taskId);
        if (!$task) {
            return false;
        }
        
        $newStatus = TaskStatus::find($newStatusId);
        if (!$newStatus || !$newStatus->is_active) {
            return false;
        }
        
        // If same status, allow position change
        if ($task->status_id === $newStatusId) {
            return true;
        }
        
        // Check if transition is allowed
        return $newStatus->isValidTransition($task->status_id);
    }

    /**
     * Move task to new status and position
     * 
     * @param int $taskId
     * @param int $newStatusId
     * @param int|null $position
     * @return array
     */
    public function moveTask(int $taskId, int $newStatusId, ?int $position = null): array
    {
        $task = Task::find($taskId);
        if (!$task) {
            return ['success' => false, 'error' => 'Task not found'];
        }
        
        if (!$this->validateStatusTransition($taskId, $newStatusId)) {
            return ['success' => false, 'error' => 'Invalid status transition'];
        }
        
        $oldStatusId = $task->status_id;
        $oldPosition = $task->position;
        
        // Calculate new position if not provided
        if ($position === null) {
            $position = $this->calculateTaskPosition($newStatusId, null);
        }
        
        // Adjust positions for other tasks
        $this->adjustTaskPositions($newStatusId, $position, $taskId);
        
        // Move the task
        $result = $task->moveToStatus($newStatusId, $position);
        
        if ($result) {
            // Log the movement
            $this->logTaskMovement($taskId, $oldStatusId, $newStatusId, $oldPosition, $position);
            
            // Broadcast update
            $this->broadcastTaskUpdate([
                'task_id' => $taskId,
                'old_status_id' => $oldStatusId,
                'new_status_id' => $newStatusId,
                'old_position' => $oldPosition,
                'new_position' => $position
            ]);
            
            return [
                'success' => true,
                'task' => $this->getTaskDetails($taskId),
                'logs' => $this->getRecentTaskLogs($taskId)
            ];
        }
        
        return ['success' => false, 'error' => 'Failed to move task'];
    }

    /**
     * Calculate next position in status column
     * 
     * @param int $statusId
     * @param int|null $targetPosition
     * @return int
     */
    public function calculateTaskPosition(int $statusId, ?int $targetPosition = null): int
    {
        if ($targetPosition !== null) {
            return $targetPosition;
        }
        
        $maxPosition = Task::where('status_id', $statusId)->max('position') ?? 0;
        return $maxPosition + 1;
    }

    /**
     * Adjust positions of other tasks when inserting at specific position
     * 
     * @param int $statusId
     * @param int $position
     * @param int $excludeTaskId
     * @return void
     */
    protected function adjustTaskPositions(int $statusId, int $position, int $excludeTaskId): void
    {
        // Shift tasks down to make room for the new position
        Task::where('status_id', $statusId)
            ->where('position', '>=', $position)
            ->where('id', '!=', $excludeTaskId)
            ->increment('position');
    }

    /**
     * Log task movement activity
     * 
     * @param int $taskId
     * @param int $fromStatusId
     * @param int $toStatusId
     * @param int $oldPosition
     * @param int $newPosition
     * @return void
     */
    public function logTaskMovement(int $taskId, int $fromStatusId, int $toStatusId, int $oldPosition, int $newPosition): void
    {
        $fromStatus = TaskStatus::find($fromStatusId);
        $toStatus = TaskStatus::find($toStatusId);
        
        if ($fromStatusId === $toStatusId) {
            $content = sprintf(
                'Task position changed from %d to %d in "%s"',
                $oldPosition,
                $newPosition,
                $toStatus ? $toStatus->title : 'Unknown Status'
            );
        } else {
            $content = sprintf(
                'Task moved from "%s" (pos %d) to "%s" (pos %d)',
                $fromStatus ? $fromStatus->title : 'Unknown',
                $oldPosition,
                $toStatus ? $toStatus->title : 'Unknown',
                $newPosition
            );
        }
        
        TaskLog::create([
            'task_id' => $taskId,
            'content' => $content,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Broadcast task update event
     * 
     * @param array $taskData
     * @return void
     */
    public function broadcastTaskUpdate(array $taskData): void
    {
        $this->fireEvent('kanban.taskMoved', $taskData);
    }

    /**
     * Get detailed task information
     * 
     * @param int $taskId
     * @return array|null
     */
    public function getTaskDetails(int $taskId): ?array
    {
        $task = Task::with(['creator', 'assigned', 'status', 'priority'])->find($taskId);
        
        if (!$task) {
            return null;
        }
        
        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description ?? '',
            'begin_date' => $task->begin_date,
            'end_date' => $task->end_date,
            'status' => [
                'id' => $task->status->id,
                'title' => $task->status->title,
                'color' => $task->status->getColorClass()
            ],
            'priority' => [
                'id' => $task->priority->id ?? 1,
                'title' => $task->priority->title ?? 'Unknown',
                'color' => $task->priority->color ?? 'secondary'
            ],
            'creator' => [
                'id' => $task->creator->id,
                'name' => $task->creator->name ?? 'Unknown User'
            ],
            'assigned' => $task->assigned ? [
                'id' => $task->assigned->id,
                'name' => $task->assigned->name ?? 'Unknown User'
            ] : null,
            'position' => $task->position,
            'created_at' => $task->created_at,
            'updated_at' => $task->updated_at
        ];
    }

    /**
     * Get recent task logs
     * 
     * @param int $taskId
     * @param int $limit
     * @return array
     */
    public function getRecentTaskLogs(int $taskId, int $limit = 5): array
    {
        $logs = TaskLog::where('task_id', $taskId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
        
        return $logs->map(function($log) {
            return [
                'id' => $log->id,
                'content' => $log->content,
                'created_at' => $log->created_at
            ];
        })->toArray();
    }

    /**
     * Get user avatar path
     * 
     * @param User $user
     * @return string
     */
    protected function getUserAvatar(User $user): string
    {
        // Default avatar path - can be customized based on actual user avatar implementation
        return "/avatars/{$user->id}.png";
    }
}