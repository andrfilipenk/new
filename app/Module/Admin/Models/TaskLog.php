<?php
// app/Module/Admin/Models/TaskLog.php
namespace Module\Admin\Models;

use Core\Database\Model;

class TaskLog extends Model
{
    protected $table = 'tasks_log';
    protected $primaryKey = 'id';
    
    public function task()
    {
        return $this->belongsTo(Tasks::class, 'task_id', 'id');
    }
}