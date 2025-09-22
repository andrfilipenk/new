<?php
// app/Module/Admin/Models/Users.php
namespace Module\Admin\Models;

use Core\Database\Model;

class Users extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'user_id';
    protected array $fillable = ['name', 'email', 'password', 'kuhnle_id'];
    #protected array $with = ['tasks']; // Always eager load task

    public function createdTasks()
    {
        return $this->hasMany(Tasks::class, 'created_by', 'user_id');
    }

    public function assignedTasks()
    {
        return $this->hasMany(Tasks::class, 'assigned_to', 'user_id');
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
