<?php
// app/Intern/Model/Task.php
namespace Intern\Model;

use Core\Database\Model;
use Admin\Model\User;

class Task extends Model
{
    const STATUS_OPEN       = 1;
    const STATUS_PROGRESS   = 2;
    const STATUS_DONE       = 3;
    
    protected $table = 'task';
    protected $primaryKey = 'id';
    protected array $fillable = [
        'title', 
        'begin_date', 
        'end_date', 
        'created_by', 
        'assigned_to', 
        'status', 
        'priority'
    ];
    
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function assigned()
    {
        return $this->belongsTo(User::class, 'assigned_to', 'id');
    }

    public function logs()
    {
        return $this->hasMany(TaskLog::class, 'task_id', 'id');
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class, 'task_id', 'id');
    }
}