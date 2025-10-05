<?php
// app/Intern/Model/Task.php
namespace Intern\Model;

use Core\Database\Model;
use Admin\Model\User;
use Core\Events\EventAware;

class Task extends Model
{
    use EventAware;
    protected $table = 'task';
    protected $primaryKey = 'id';
    protected array $fillable = [
        'title', 
        'description', 
        'begin_date', 
        'end_date', 
        
        'created_by', 
        'assigned_to', 
        'status_id', 
        'priority_id',
        'position'
    ];
    
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function assigned()
    {
        return $this->belongsTo(User::class, 'assigned_to', 'id');
    }

    public function status()
    {
        return $this->belongsTo(TaskStatus::class, 'status_id', 'id');
    }

    public function priority()
    {
        return $this->belongsTo(TaskPriority::class, 'priority_id', 'id');
    }

    public function logs()
    {
        return $this->hasMany(TaskLog::class, 'task_id', 'id');
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class, 'task_id', 'id');
    }

    /**
     * Move task to a different status with optional position
     * 
     * @param int $statusId
     * @param int|null $position
     * @return bool
     */
    public function moveToStatus(int $statusId, ?int $position = null): bool
    {
        $oldStatusId = $this->status_id;
        $this->status_id = $statusId;
        
        if ($position !== null) {
            $this->position = $position;
        } else {
            // Get next position in the new status
            $maxPosition = static::where('status_id', $statusId)
                ->max('position') ?? 0;
            $this->position = $maxPosition + 1;
        }
        
        $result = $this->save();
        
        if ($result && $oldStatusId !== $statusId) {
            // Log the status change
            $this->logStatusChange($oldStatusId, $statusId);
            
            // Fire event for status change
            $this->fireEvent('task.moved', [
                'task_id' => $this->id,
                'from_status' => $oldStatusId,
                'to_status' => $statusId,
                'position' => $this->position
            ]);
        }
        
        return $result;
    }

    /**
     * Update task position within the same status
     * 
     * @param int $position
     * @return bool
     */
    public function updatePosition(int $position): bool
    {
        $oldPosition = $this->position;
        $this->position = $position;
        $result = $this->save();
        
        if ($result && $oldPosition !== $position) {
            $this->fireEvent('task.positionChanged', [
                'task_id' => $this->id,
                'status_id' => $this->status_id,
                'old_position' => $oldPosition,
                'new_position' => $position
            ]);
        }
        
        return $result;
    }

    /**
     * Get tasks ordered by position for kanban display
     * 
     * @param int $statusId
     * @return \Core\Database\Collection
     */
    public static function getByStatusOrdered(int $statusId)
    {
        return static::where('status_id', $statusId)
            ->orderBy('position', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Log status change
     * 
     * @param int $fromStatusId
     * @param int $toStatusId
     * @return void
     */
    protected function logStatusChange(int $fromStatusId, int $toStatusId): void
    {
        $fromStatus = TaskStatus::find($fromStatusId);
        $toStatus = TaskStatus::find($toStatusId);
        
        $content = sprintf(
            'Task moved from "%s" to "%s"',
            $fromStatus ? $fromStatus->title : 'Unknown',
            $toStatus ? $toStatus->title : 'Unknown'
        );
        
        TaskLog::create([
            'task_id' => $this->id,
            'content' => $content,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}