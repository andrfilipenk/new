<?php
// app/Intern/Model/TaskLog.php
namespace Intern\Model;

use Core\Database\Model;

class TaskLog extends Model
{
    protected $table = 'task_log';
    protected $primaryKey = 'id';
    
    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id', 'id');
    }
}