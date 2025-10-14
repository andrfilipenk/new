<?php
// app/Core/Acl/Role.php
namespace Core\Acl;

use Core\Database\Model;
use Core\Database\Model\BelongsToMany;

class Role extends Model
{
    protected $table = 'acl_role';
    protected $primaryKey = 'id';
    protected array $fillable = ['name', 'display_name', 'description'];

    /**
     * Undocumented function
     *
     * @return BelongsToMany
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'acl_role_permission', 'role_id', 'permission_id');
    }
    
    /**
     * 
     * 
     * @return Model\BelongsToMany|User[]
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'acl_user_role', 'role_id', 'user_id');
    }

    /**
     *
     * @param string $permission
     * @return boolean
     */
    public function hasPermission(string $permission): bool
    {
        return $this->permissions()->where('name', $permission)->exists();
    }

    /**
     *
     * @param string|Permission $permission
     * @return boolean
     */
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

    /**
     *
     * @param string|Permission $permission
     * @return boolean
     */
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

    /**
     *
     * @param array $permissions
     * @return void
     */
    public function syncPermissions(array $permissions): void
    {
        $this->permissions()->sync(array_filter(array_map(fn($perm) => is_string($perm) ? Permission::where('name', $perm)->first()->id : ($perm instanceof Permission ? $perm->id : null), $permissions)));
    }

    /*
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
    } */
}