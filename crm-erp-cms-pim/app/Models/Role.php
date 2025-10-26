<?php

namespace App\Models;

use Core\ORM\Model;

/**
 * Role Model
 */
class Role extends Model
{
    protected string $table = 'roles';
    
    protected array $fillable = [
        'name',
        'slug',
        'description',
        'level',
    ];
    
    protected array $casts = [
        'level' => 'integer',
    ];
    
    protected bool $timestamps = true;
    
    /**
     * Get role permissions
     */
    public function permissions(): array
    {
        if (isset($this->relations['permissions'])) {
            return $this->relations['permissions'];
        }
        
        $permissions = \Core\Database\DB::table('permissions')
            ->select('permissions.*')
            ->join('permission_role', 'permissions.id', '=', 'permission_role.permission_id')
            ->where('permission_role.role_id', '=', $this->id)
            ->get();
        
        $this->relations['permissions'] = array_map(function($row) {
            return Permission::hydrate($row);
        }, $permissions);
        
        return $this->relations['permissions'];
    }
    
    /**
     * Get role users
     */
    public function users(): array
    {
        if (isset($this->relations['users'])) {
            return $this->relations['users'];
        }
        
        $users = \Core\Database\DB::table('users')
            ->select('users.*')
            ->join('role_user', 'users.id', '=', 'role_user.user_id')
            ->where('role_user.role_id', '=', $this->id)
            ->get();
        
        $this->relations['users'] = array_map(function($row) {
            return User::hydrate($row);
        }, $users);
        
        return $this->relations['users'];
    }
    
    /**
     * Attach permission to role
     */
    public function attachPermission(int $permissionId): void
    {
        \Core\Database\DB::table('permission_role')->insert([
            'role_id' => $this->id,
            'permission_id' => $permissionId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        // Clear cached permissions
        unset($this->relations['permissions']);
    }
    
    /**
     * Detach permission from role
     */
    public function detachPermission(int $permissionId): void
    {
        \Core\Database\DB::table('permission_role')
            ->where('role_id', '=', $this->id)
            ->where('permission_id', '=', $permissionId)
            ->delete();
        
        // Clear cached permissions
        unset($this->relations['permissions']);
    }
    
    /**
     * Sync role permissions
     */
    public function syncPermissions(array $permissionIds): void
    {
        // Remove all existing permissions
        \Core\Database\DB::table('permission_role')
            ->where('role_id', '=', $this->id)
            ->delete();
        
        // Attach new permissions
        foreach ($permissionIds as $permissionId) {
            $this->attachPermission($permissionId);
        }
    }
}
