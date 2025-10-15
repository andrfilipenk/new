<?php
// app/Intern/Model/Task.php
namespace Intern\Model;

use Core\Database\Model;
use Core\Utils\Dates;
use User\Model\User;

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
        'status_id', 
        'priority_id'
    ];

    public function dateFormated($column)
    {
        $date = Dates::createFromString($this->$column);
        return $date->format("d.m.Y");
    }
    
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
}