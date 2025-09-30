<?php
// app/Admin/Model/Groups.php
namespace Admin\Model;

use Core\Database\Model;

class Groups extends Model
{
    protected $table = 'groups';
    protected $primaryKey = 'id';
    protected array $fillable = ['name', 'code'];

    /**
     * Get all users belonging to this group
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_group', 'group_id', 'user_id');
    }

    /**
     * Create a new group
     */
    public static function createGroup(string $name, string $code): ?self
    {
        $group = new self();
        $group->name = $name;
        $group->code = $code;
        return $group->save() ? $group : null;
    }
}
