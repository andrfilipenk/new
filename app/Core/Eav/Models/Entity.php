<?php
// app/Eav/Models/Entity.php
namespace Eav\Models;

use Core\Database\Model;

/**
 * Entity Model
 * 
 * Represents an EAV entity instance
 */
class Entity extends Model
{
    protected $table = 'eav_entities';
    protected bool $softDeletes = true;

    protected array $fillable = [
        'entity_type_id',
        'parent_id',
        'entity_code',
        'is_active'
    ];

    protected array $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * Get the entity type
     */
    public function entityType()
    {
        return $this->belongsTo(EntityType::class, 'entity_type_id');
    }

    /**
     * Get parent entity
     */
    public function parent()
    {
        return $this->belongsTo(Entity::class, 'parent_id');
    }

    /**
     * Get child entities
     */
    public function children()
    {
        return $this->hasMany(Entity::class, 'parent_id');
    }

    /**
     * Check if entity is active
     */
    public function isActive(): bool
    {
        return (bool)$this->is_active;
    }
}
