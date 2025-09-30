<?php
// app/Intern/Model/TaskPriority.php
namespace Intern\Model;

use Core\Database\Model;

class TaskPriority extends Model
{
    protected $table = 'task_priority';
    protected $primaryKey = 'id';
    
    public function tasks()
    {
        return $this->hasMany(Task::class, 'priority_id', 'id');
    }
}