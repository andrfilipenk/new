<?php
// app/Intern/Model/TaskStatus.php
namespace Intern\Model;

use Core\Database\Model;

class TaskStatus extends Model
{
    protected $table = 'task_status';
    protected $primaryKey = 'id';
    
    public function tasks()
    {
        return $this->hasMany(Task::class, 'status_id', 'id');
    }
}