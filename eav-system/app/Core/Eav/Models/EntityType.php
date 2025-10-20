<?php
// app/Eav/Models/EntityType.php
namespace Eav\Models;

use Core\Database\Model;

/**
 * Entity Type Model
 * 
 * Represents an EAV entity type definition
 */
class EntityType extends Model
{
    protected $table = 'eav_entity_types';

    protected array $fillable = [
        'entity_type_code',
        'entity_type_name',
        'description',
        'entity_table',
        'is_active'
    ];

    protected array $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * Get all attributes for this entity type
     */
    public function attributes()
    {
        return $this->hasMany(Attribute::class, 'entity_type_id');
    }

    /**
     * Get all entities of this type
     */
    public function entities()
    {
        return $this->hasMany(Entity::class, 'entity_type_id');
    }

    /**
     * Check if entity type is active
     */
    public function isActive(): bool
    {
        return (bool)$this->is_active;
    }
}
