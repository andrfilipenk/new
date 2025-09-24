<?php
// app/Module/Admin/Models/User.php
namespace Module\Admin\Models;

use Core\Database\Model;
use Module\Base\Model\Task;

class User extends \Core\Acl\User
{

    public function createdTasks()
    {
        #return $this->belongsTo(Task::class, 'created_by', 'id')->getResults();
        return $this->hasMany(Task::class, 'created_by', 'id');
    }

    public function assignedTasks()
    {
        return $this->hasMany(Task::class, 'assigned_to', 'id');
    }

    public function taskComments()
    {
        return $this->hasMany(TaskComment::class, 'user_id', 'id');
    }
    
}


/* 
// In controller or elsewhere:
$users = Users::with([
    'profile', 
    'posts' => fn($q) => $q->where('published', 1), 
    'posts.comments'
])->get();

*/
