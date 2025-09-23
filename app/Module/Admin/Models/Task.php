<?php
// app/Module/Admin/Models/Task.php
namespace Module\Admin\Models;

use Core\Database\Model;
use Module\Admin\Models\User;

class Task extends Model
{
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
}