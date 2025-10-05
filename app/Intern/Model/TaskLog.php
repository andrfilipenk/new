<?php
// app/Intern/Model/TaskLog.php
namespace Intern\Model;

use Core\Database\Model;
use Admin\Model\User;

class TaskLog extends Model
{
    protected $table = 'task_log';
    protected $primaryKey = 'id';
    protected array $fillable = ['task_id', 'content', 'user_id', 'log_type', 'metadata', 'created_at'];
    
    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Create a kanban movement log entry
     * 
     * @param int $taskId
     * @param int $fromStatusId
     * @param int $toStatusId
     * @param int $fromPosition
     * @param int $toPosition
     * @param int|null $userId
     * @return static
     */
    public static function logKanbanMovement(
        int $taskId,
        int $fromStatusId,
        int $toStatusId,
        int $fromPosition,
        int $toPosition,
        ?int $userId = null
    ): self {
        $fromStatus = TaskStatus::find($fromStatusId);
        $toStatus = TaskStatus::find($toStatusId);
        
        if ($fromStatusId === $toStatusId) {
            $content = sprintf(
                'Task position changed from %d to %d in "%s"',
                $fromPosition,
                $toPosition,
                $toStatus ? $toStatus->title : 'Unknown Status'
            );
        } else {
            $content = sprintf(
                'Task moved from "%s" (pos %d) to "%s" (pos %d)',
                $fromStatus ? $fromStatus->title : 'Unknown',
                $fromPosition,
                $toStatus ? $toStatus->title : 'Unknown',
                $toPosition
            );
        }
        
        return static::create([
            'task_id' => $taskId,
            'content' => $content,
            'user_id' => $userId,
            'log_type' => 'kanban_movement',
            'metadata' => json_encode([
                'from_status_id' => $fromStatusId,
                'to_status_id' => $toStatusId,
                'from_position' => $fromPosition,
                'to_position' => $toPosition
            ]),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Create a task creation log entry
     * 
     * @param int $taskId
     * @param int $createdBy
     * @param int|null $assignedTo
     * @return static
     */
    public static function logTaskCreation(int $taskId, int $createdBy, ?int $assignedTo = null): self
    {
        $creator = User::find($createdBy);
        $assignee = $assignedTo ? User::find($assignedTo) : null;
        
        $content = sprintf(
            'Task created by %s',
            $creator ? $creator->name : 'Unknown User'
        );
        
        if ($assignee && $assignedTo !== $createdBy) {
            $content .= sprintf(' and assigned to %s', $assignee->name);
        }
        
        return static::create([
            'task_id' => $taskId,
            'content' => $content,
            'user_id' => $createdBy,
            'log_type' => 'task_created',
            'metadata' => json_encode([
                'created_by' => $createdBy,
                'assigned_to' => $assignedTo
            ]),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Create a task update log entry
     * 
     * @param int $taskId
     * @param array $updatedFields
     * @param int|null $userId
     * @return static
     */
    public static function logTaskUpdate(int $taskId, array $updatedFields, ?int $userId = null): self
    {
        $user = $userId ? User::find($userId) : null;
        
        $content = sprintf(
            'Task updated (%s) by %s',
            implode(', ', $updatedFields),
            $user ? $user->name : 'Unknown User'
        );
        
        return static::create([
            'task_id' => $taskId,
            'content' => $content,
            'user_id' => $userId,
            'log_type' => 'task_updated',
            'metadata' => json_encode([
                'updated_fields' => $updatedFields
            ]),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get logs for a specific task
     * 
     * @param int $taskId
     * @param int $limit
     * @return \Core\Database\Collection
     */
    public static function getTaskLogs(int $taskId, int $limit = 10)
    {
        return static::where('task_id', $taskId)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get logs by type
     * 
     * @param string $logType
     * @param int $limit
     * @return \Core\Database\Collection
     */
    public static function getLogsByType(string $logType, int $limit = 50)
    {
        return static::where('log_type', $logType)
            ->with(['task', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get metadata as array
     * 
     * @return array
     */
    public function getMetadataArray(): array
    {
        return $this->metadata ? json_decode($this->metadata, true) : [];
    }

    /**
     * Set metadata from array
     * 
     * @param array $metadata
     * @return void
     */
    public function setMetadataArray(array $metadata): void
    {
        $this->metadata = json_encode($metadata);
    }
}