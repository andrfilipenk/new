<?php
namespace Module\Admin\Models;

use Core\Database\Model;
use Module\Admin\Models\Users;

class Tasks extends Model
{
    protected $table = 'tasks';
    protected $primaryKey = 'task_id';
    protected array $fillable = [
        'created_by', 
        'assigned_to', 
        'title', 
        'created_date', 
        'begin_date', 
        'end_date', 
        'status', 
        'priority'
    ];

    // Relationship: creator
    public function creator()
    {
        return $this->belongsTo(Users::class, 'created_by', 'user_id');
    }

    // Relationship: assigned user
    public function assigned()
    {
        return $this->belongsTo(Users::class, 'assigned_to', 'user_id');
    }
}


/*

CREATE TABLE tasks (
    task_id INT AUTO_INCREMENT PRIMARY KEY,
    created_by INT NOT NULL,
    assigned_to INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    created_date DATE NOT NULL,
    begin_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'open',
    priority INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(user_id) ON DELETE SET NULL
);
*/