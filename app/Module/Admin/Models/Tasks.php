<?php
// app/Module/Admin/Models/Tasks.php
namespace Module\Admin\Models;

use Core\Database\Model;
use Module\Admin\Models\Users;

class Tasks extends Model
{
    protected $table = 'tasks';
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
        return $this->belongsTo(Users::class, 'created_by', 'user_id');
    }

    public function assigned()
    {
        return $this->belongsTo(Users::class, 'assigned_to', 'user_id');
    }

    public function logs()
    {
        return $this->hasMany(Tasks::class, 'task_id', 'id');
    }
}