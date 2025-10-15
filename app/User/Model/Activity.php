<?php
// app/User/Model/Activity.php
namespace User\Model;

use Core\Database\Model;

class Activity extends Model
{
    protected $table = 'task_log';
    protected $primaryKey = 'id';

    protected array $fillable = [
        'content',
        'created_at', 
    ];


    static public function getRecent($limit = 10)
    {
        return self::query()->orderBy('id', 'DESC')->limit($limit)->get();
    }
}