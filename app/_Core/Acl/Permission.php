<?php
// app/_Core/Acl/Permission.php
namespace Core\Acl;

use Core\Database\Model;

class Permission extends Model
{
    protected $table = 'acl_permission';
    protected $primaryKey = 'id';
    protected array $fillable = ['name', 'display_name', 'description', 'resource', 'action'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'acl_role_permission', 'permission_id', 'role_id');
    }

    /**
     * 
     * @return Model\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'acl_user_permission', 'permission_id', 'user_id')
                    ->withPivot('granted');
    }

    public static function createForResource(string $resource, string $action, string $displayName = null, string $description = null): self
    {
        $name        = $resource . '.' . $action;
        $displayName = $displayName ?: ucfirst($action) . ' ' . ucfirst($resource);
        return static::create([
            'name'          => $name,
            'display_name'  => $displayName,
            'description'   => $description,
            'resource'      => $resource,
            'action'        => $action
        ]);
    }

    public static function byResource(string $resource): array
    {
        return static::where('resource', $resource)->get();
    }

    public static function byAction(string $action): array
    {
        return static::where('action', $action)->get();
    }

    public function matches(string $pattern): bool
    {
        // Convert pattern to regex
        $regex = str_replace(['*', '.'], ['.*', '\.'], $pattern);
        return preg_match('/^' . $regex . '$/', $this->name);
    }
}