<?php
// app/User/Model/User.php
namespace User\Model;

use Intern\Model\Task;
use Intern\Model\TaskComment;

class User extends \Core\Acl\User
{

    /**
     *
     * @param int $id
     */
    static public function byCustomID($id)
    {
        return self::find($id, 'custom_id');
    }

    /**
     * Get groups this user belongs to
     *
     * @return \Core\Database\Model\BelongsToMany
     */
    public function groups()
    {
        return $this->belongsToMany(Groups::class, 'user_group', 'user_id', 'group_id');
    }

    /**
     * Add user to group(s)
     */
    public function addToGroup($groupId)
    {
        if (is_array($groupId)) {
            foreach ($groupId as $id) {
                $this->groups()->attach($id);
            }
        } else {
            $this->groups()->attach($groupId);
        }
        return $this;
    }

    /**
     * Remove user from group(s)
     */
    public function removeFromGroup($groupId)
    {
        if (is_array($groupId)) {
            $this->groups()->detach($groupId);
        } else {
            $this->groups()->detach($groupId);
        }
        return $this;
    }

    /**
     * Sync user groups (replace all current groups)
     */
    public function syncGroups(array $groupIds)
    {
        $this->groups()->sync($groupIds);
        return $this;
    }

    /**
     * Check if user belongs to group
     */
    public function belongsToGroup($groupId): bool
    {
        // Simple pivot table query
        return $this->db()->table('user_group')
            ->where('user_id', $this->getKey())
            ->where('group_id', $groupId)
            ->count() > 0;
    }

    /**
     * Assign ACL role to user by ID
     */
    public function assignRoleById($roleId)
    {
        // Check if role assignment already exists
        $existing = $this->db()->table('acl_user_role')
            ->where('user_id', $this->getKey())
            ->where('role_id', $roleId)
            ->first();
            
        if (!$existing) {
            $this->db()->table('acl_user_role')->insert([
                'user_id' => $this->getKey(),
                'role_id' => $roleId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
        return $this;
    }

    /**
     * Remove ACL role from user by ID
     */
    public function removeRoleById($roleId)
    {
        $this->db()->table('acl_user_role')
            ->where('user_id', $this->getKey())
            ->where('role_id', $roleId)
            ->delete();
        return $this;
    }

    public function createdTasks()
    {
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