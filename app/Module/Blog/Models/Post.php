<?php
namespace Module\Blog\Models;

use Core\Database\Model;

use Module\User\Models\User as UserModel;

class Post extends Model
{
    public function author()
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
