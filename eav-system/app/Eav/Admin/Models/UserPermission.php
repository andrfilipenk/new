<?php

namespace Eav\Admin\Models;

use Core\Database\Model;

class UserPermission extends Model
{
    protected string $table = 'eav_user_permissions';
    protected string $primaryKey = 'permission_id';
    protected bool $timestamps = true;
    
    protected array $fillable = [
        'user_id',
        'role',
        'entity_type_id',
        'permissions',
        'row_filter',
        'hidden_attributes'
    ];
    
    protected array $casts = [
        'permissions' => 'json',
        'row_filter' => 'json',
        'hidden_attributes' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_ADMIN = 'admin';
    const ROLE_DATA_MANAGER = 'data_manager';
    const ROLE_USER = 'user';
    const ROLE_API_CLIENT = 'api_client';
    
    /**
     * Get the user this permission belongs to
     */
    public function user()
    {
        return $this->belongsTo(\User\Model\User::class, 'user_id', 'id');
    }
    
    /**
     * Get the entity type
     */
    public function entityType()
    {
        return $this->belongsTo(\Eav\Models\EntityType::class, 'entity_type_id', 'entity_type_id');
    }
    
    /**
     * Check if user has specific permission
     */
    public function can(string $permission): bool
    {
        $perms = $this->permissions ?? [];
        return isset($perms[$permission]) && $perms[$permission] === true;
    }
    
    /**
     * Check if attribute is hidden
     */
    public function isAttributeHidden(string $attributeCode): bool
    {
        $hidden = $this->hidden_attributes ?? [];
        return in_array($attributeCode, $hidden);
    }
    
    /**
     * Get allowed entity IDs based on row filter
     */
    public function getRowFilter(): ?array
    {
        return $this->row_filter;
    }
}
