<?php

namespace App\Models;

use Core\ORM\Model;
use Core\Security\PasswordHasher;

/**
 * User Model
 */
class User extends Model
{
    protected string $table = 'users';
    
    protected array $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'email_verified_at',
        'last_login_at',
    ];
    
    protected array $hidden = [
        'password',
        'remember_token',
    ];
    
    protected array $casts = [
        'is_active' => 'boolean',
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];
    
    protected bool $timestamps = true;
    protected bool $softDeletes = true;
    
    /**
     * Hash password when setting
     */
    public function setPasswordAttribute(string $value): string
    {
        return PasswordHasher::hash($value);
    }
    
    /**
     * Verify password
     */
    public function verifyPassword(string $password): bool
    {
        return PasswordHasher::verify($password, $this->password);
    }
    
    /**
     * Check if user has a specific role
     */
    public function hasRole(string $roleSlug): bool
    {
        $roles = $this->roles();
        
        foreach ($roles as $role) {
            if ($role->slug === $roleSlug) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roleSlugs): bool
    {
        foreach ($roleSlugs as $slug) {
            if ($this->hasRole($slug)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permissionSlug): bool
    {
        $roles = $this->roles();
        
        foreach ($roles as $role) {
            $permissions = $role->permissions();
            
            foreach ($permissions as $permission) {
                if ($permission->slug === $permissionSlug) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get user roles
     */
    public function roles(): array
    {
        if (isset($this->relations['roles'])) {
            return $this->relations['roles'];
        }
        
        $roles = \Core\Database\DB::table('roles')
            ->select('roles.*')
            ->join('role_user', 'roles.id', '=', 'role_user.role_id')
            ->where('role_user.user_id', '=', $this->id)
            ->get();
        
        $this->relations['roles'] = array_map(function($row) {
            return Role::hydrate($row);
        }, $roles);
        
        return $this->relations['roles'];
    }
    
    /**
     * Attach role to user
     */
    public function attachRole(int $roleId): void
    {
        \Core\Database\DB::table('role_user')->insert([
            'user_id' => $this->id,
            'role_id' => $roleId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        // Clear cached roles
        unset($this->relations['roles']);
    }
    
    /**
     * Detach role from user
     */
    public function detachRole(int $roleId): void
    {
        \Core\Database\DB::table('role_user')
            ->where('user_id', '=', $this->id)
            ->where('role_id', '=', $roleId)
            ->delete();
        
        // Clear cached roles
        unset($this->relations['roles']);
    }
    
    /**
     * Sync user roles
     */
    public function syncRoles(array $roleIds): void
    {
        // Remove all existing roles
        \Core\Database\DB::table('role_user')
            ->where('user_id', '=', $this->id)
            ->delete();
        
        // Attach new roles
        foreach ($roleIds as $roleId) {
            $this->attachRole($roleId);
        }
    }
}
