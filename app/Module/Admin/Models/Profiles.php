<?php
// app/Module/Admin/Models/Profiles.php
namespace Module\Admin\Models;

use Core\Database\Model;

class Profiles extends Model
{
    protected $primaryKey = 'user_id';

    /*
        CREATE TABLE profiles (
        profile_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        bio TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    );
    */
    
    public function profile()
    {
        return $this->hasOne(Profiles::class);
    }
}