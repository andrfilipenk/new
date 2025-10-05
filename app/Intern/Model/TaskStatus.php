<?php
// app/Intern/Model/TaskStatus.php
namespace Intern\Model;

use Core\Database\Model;

class TaskStatus extends Model
{
    protected $table = 'task_status';
    protected $primaryKey = 'id';
    protected array $fillable = ['title', 'code', 'color', 'position', 'is_active'];
    
    public function tasks()
    {
        return $this->hasMany(Task::class, 'status_id', 'id');
    }

    /**
     * Get statuses ordered by position for kanban display
     * 
     * @return \Core\Database\Collection
     */
    public static function getKanbanStatuses()
    {
        return static::where('is_active', true)
            ->orderBy('position', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Check if transition from one status to another is valid
     * 
     * @param int $fromStatusId
     * @return bool
     */
    public function isValidTransition(int $fromStatusId): bool
    {
        // Define allowed transitions based on business rules
        $transitions = [
            1 => [2, 4], // New -> In Progress, On Hold
            2 => [3, 4, 1], // In Progress -> Completed, On Hold, New
            3 => [2], // Completed -> In Progress
            4 => [1, 2], // On Hold -> New, In Progress
        ];
        
        return isset($transitions[$fromStatusId]) && in_array($this->id, $transitions[$fromStatusId]);
    }

    /**
     * Get display order for kanban columns
     * 
     * @return int
     */
    public function getDisplayOrder(): int
    {
        return $this->position ?? $this->id;
    }

    /**
     * Get task count for this status
     * 
     * @return int
     */
    public function getTaskCount(): int
    {
        return $this->tasks()->count();
    }

    /**
     * Get CSS color class for this status
     * 
     * @return string
     */
    public function getColorClass(): string
    {
        return $this->color ?? 'secondary';
    }
}