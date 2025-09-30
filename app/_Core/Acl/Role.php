<?php
// app/_Core/Acl/Role.php
namespace Core\Acl;

use Core\Database\Model;

class Role extends Model
{
    protected $table = 'acl_role';
    protected $primaryKey = 'id';
    protected array $fillable = ['name', 'display_name', 'description'];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'acl_role_permission', 'role_id', 'permission_id');
    }
    
    public function users()
    {
        return $this->belongsToMany(User::class, 'acl_user_role', 'role_id', 'user_id');
    }
    
    public function hasPermission(string $permission): bool
    {
        return $this->permissions()->where('name', $permission)->exists();
    }
    public function givePermission(string|Permission $permission): bool
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->first();
            if (!$permission) {
                return false;
            }
        }
        if (!$this->hasPermission($permission->name)) {
            $this->permissions()->attach($permission->id);
            return true;
        }
        return false;
    }

    public function revokePermission(string|Permission $permission): bool
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->first();
            if (!$permission) {
                return false;
            }
        }
        $this->permissions()->detach($permission->id);
        return true;
    }

    public function syncPermissions(array $permissions): void
    {
        $permissionIds = [];
        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                $perm = Permission::where('name', $permission)->first();
                if ($perm) {
                    $permissionIds[] = $perm->id;
                }
            } elseif ($permission instanceof Permission) {
                $permissionIds[] = $permission->id;
            }
        }
        $this->permissions()->sync($permissionIds);
    }
}