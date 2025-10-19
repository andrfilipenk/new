<?php

namespace Eav\Admin\Models;

use Core\Database\Model;

class EntityVersion extends Model
{
    protected string $table = 'eav_entity_versions';
    protected string $primaryKey = 'version_id';
    protected bool $timestamps = false;
    
    protected array $fillable = [
        'entity_id',
        'entity_type_id',
        'version_number',
        'attribute_snapshots',
        'changed_attributes',
        'user_id',
        'change_description'
    ];
    
    protected array $casts = [
        'attribute_snapshots' => 'json',
        'changed_attributes' => 'json',
        'created_at' => 'datetime'
    ];
    
    /**
     * Get the entity type this version belongs to
     */
    public function entityType()
    {
        return $this->belongsTo(\Eav\Models\EntityType::class, 'entity_type_id', 'entity_type_id');
    }
    
    /**
     * Get the user who created this version
     */
    public function user()
    {
        return $this->belongsTo(\User\Model\User::class, 'user_id', 'id');
    }
    
    /**
     * Get the diff between two versions
     */
    public function getDiffWith(EntityVersion $otherVersion): array
    {
        $thisSnapshot = $this->attribute_snapshots ?? [];
        $otherSnapshot = $otherVersion->attribute_snapshots ?? [];
        
        $changes = [];
        $allKeys = array_unique(array_merge(array_keys($thisSnapshot), array_keys($otherSnapshot)));
        
        foreach ($allKeys as $key) {
            $thisValue = $thisSnapshot[$key] ?? null;
            $otherValue = $otherSnapshot[$key] ?? null;
            
            if ($thisValue !== $otherValue) {
                $changes[] = [
                    'attribute' => $key,
                    'old_value' => $otherValue,
                    'new_value' => $thisValue,
                    'change_type' => $this->getChangeType($thisValue, $otherValue)
                ];
            }
        }
        
        return $changes;
    }
    
    /**
     * Determine the type of change
     */
    private function getChangeType($newValue, $oldValue): string
    {
        if ($oldValue === null && $newValue !== null) {
            return 'added';
        }
        if ($oldValue !== null && $newValue === null) {
            return 'removed';
        }
        return 'modified';
    }
}
