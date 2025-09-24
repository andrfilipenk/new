<?php
// app/Module/Base/Model/TaskComment.php
namespace Module\Base\Model;

use Core\Database\Model;
use Module\Admin\Models\User;

class TaskComment extends Model
{
    protected $table = 'task_comment';
    protected $primaryKey = 'id';
    protected $fillable = ['content'];
    
    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}