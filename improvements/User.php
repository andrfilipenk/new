<?php
// app/Admin/Model/User.php
namespace Admin\Model;

use Core\Database\Model;
use Intern\Model\Task;
use Intern\Model\TaskComment;

class User extends \Core\Acl\User
{
    /**
     * Get groups this user belongs to
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

    /**
     * Manager relationship (self-referencing)
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id', 'id');
    }

    /**
     * Subordinates relationship
     */
    public function subordinates()
    {
        return $this->hasMany(User::class, 'manager_id', 'id');
    }

    /**
     * User benefits relationship
     */
    public function benefits()
    {
        return $this->hasMany(UserBenefit::class, 'user_id', 'id');
    }

    /**
     * User documents relationship
     */
    public function documents()
    {
        return $this->hasMany(UserDocument::class, 'user_id', 'id');
    }

    /**
     * Holiday requests relationship
     */
    public function holidayRequests()
    {
        return $this->hasMany(HolidayRequest::class, 'user_id', 'id');
    }

    /**
     * Get user's full name
     */
    public function getFullNameAttribute(): string
    {
        if ($this->first_name && $this->last_name) {
            return $this->first_name . ' ' . $this->last_name;
        }
        return $this->name;
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if user is manager
     */
    public function isManager(): bool
    {
        return $this->subordinates()->count() > 0;
    }

    /**
     * Get user permissions (combined from roles and direct permissions)
     */
    public function getAllPermissions(): array
    {
        $permissions = [];
        
        // Get role-based permissions
        foreach ($this->roles as $role) {
            foreach ($role->permissions as $permission) {
                $permissions[] = $permission->name;
            }
        }
        
        // Get direct permissions
        foreach ($this->permissions as $permission) {
            if ($permission->pivot_granted) {
                $permissions[] = $permission->name;
            }
        }
        
        return array_unique($permissions);
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->getAllPermissions());
    }

    /**
     * Get user statistics
     */
    public function getStats(): array
    {
        return [
            'total_tasks_created' => $this->createdTasks()->count(),
            'total_tasks_assigned' => $this->assignedTasks()->count(),
            'completed_tasks' => $this->assignedTasks()->where('status_id', 3)->count(),
            'pending_tasks' => $this->assignedTasks()->where('status_id', 1)->count(),
            'holiday_requests' => $this->holidayRequests()->count(),
            'approved_holidays' => $this->holidayRequests()->where('granted', 1)->count(),
        ];
    }
}
