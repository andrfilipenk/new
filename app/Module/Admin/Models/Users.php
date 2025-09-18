<?php
namespace Module\Admin\Models;

use Core\Database\Model;
#use Module\Admin\Models\Profiles;

class Users extends Model
{
    protected $table = 'users';
    protected $fillable = ['name', 'email', 'password', 'created_at', 'updated_at'];
    protected $primaryKey = 'user_id';
    #protected array $with = ['profile']; // Always eager load profile

    #public function profile()
    #{
    #    return $this->hasOne(Profiles::class, 'user_id', 'user_id');
    #}
}


/*

// In controller or elsewhere:
$users = Users::with([
    'profile', 
    'posts' => fn($q) => $q->where('published', 1), 
    'posts.comments'
])->get();

*/

/* 
    CREATE TABLE users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );
     */