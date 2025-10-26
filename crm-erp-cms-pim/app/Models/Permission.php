<?php

namespace App\Models;

use Core\ORM\Model;

/**
 * Permission Model
 */
class Permission extends Model
{
    protected string $table = 'permissions';
    
    protected array $fillable = [
        'name',
        'slug',
        'module',
        'description',
    ];
    
    protected bool $timestamps = true;
    
    /**
     * Get roles that have this permission
     */
    public function roles(): array
    {
        if (isset($this->relations['roles'])) {
            return $this->relations['roles'];
        }
        
        $roles = \Core\Database\DB::table('roles')
            ->select('roles.*')
            ->join('permission_role', 'roles.id', '=', 'permission_role.role_id')
            ->where('permission_role.permission_id', '=', $this->id)
            ->get();
        
        $this->relations['roles'] = array_map(function($row) {
            return Role::hydrate($row);
        }, $roles);
        
        return $this->relations['roles'];
    }
    
    /**
     * Get permissions by module
     */
    public static function byModule(string $module): array
    {
        $rows = \Core\Database\DB::table('permissions')
            ->where('module', '=', $module)
            ->orderBy('name')
            ->get();
        
        return array_map(function($row) {
            return static::hydrate($row);
        }, $rows);
    }
}
