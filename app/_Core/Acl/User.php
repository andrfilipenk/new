<?php
// app/_Core/Acl/User.php
namespace Core\Acl;

use Core\Database\Model;
use Core\Di\Container;

class User extends Model
{
    protected $table = 'user';
    protected $primaryKey = 'id';
    protected array $fillable = ['name', 'email', 'password', 'custom_id'];
    
    // protected array $with = ['tasks']; // Always eager load task

    public function save(): bool
    {
        if (isset($this->attributes['password']) && $this->isDirty('password')) {
            $config = Container::getDefault()->get('config');
            $this->attributes['password'] = password_hash($this->attributes['password'], $config['app']['hash_algo']);
        }
        return parent::save();
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->attributes['password']);
    }

    protected function isDirty(string $attribute): bool
    {
        return !isset($this->original[$attribute]) || 
               $this->attributes[$attribute] !== $this->original[$attribute];
    }
    
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'acl_user_role', 'user_id', 'role_id');
    }
    
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'acl_user_permission', 'user_id', 'permission_id')
            ->withPivot('granted');
    }
    
    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }
    
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }
    
    public function hasAllRoles(array $roles): bool
    {
        $userRoles = $this->roles()->pluck('name')->toArray();
        return count(array_intersect($roles, $userRoles)) === count($roles);
    }
    
    public function assignRole(string|Role $role): bool
    {
        if (is_string($role)) {
            $roleObj = Role::find($role, 'name')->first();
            if (!$roleObj) {
                return false;
            }
            $role = $roleObj;
        }
        if (!$this->hasRole($role->name)) {
            $this->roles()->attach($role->id);
            return true;
        }
        return false;
    }

    public function removeRole(string|Role $role): bool
    {
        if (is_string($role)) {
            $roleObj = Role::find($role, 'name')->first();
            if (!$roleObj) {
                return false;
            }
            $role = $roleObj;
        }
        $this->roles()->detach($role->id);
        return true;
    }
}