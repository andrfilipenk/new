<?php
// app/Eav/Models/Attribute.php
namespace Eav\Models;

use Core\Database\Model;

/**
 * Attribute Model
 * 
 * Represents an EAV attribute definition
 */
class Attribute extends Model
{
    protected $table = 'eav_attributes';

    protected array $fillable = [
        'entity_type_id',
        'attribute_code',
        'attribute_name',
        'backend_type',
        'frontend_input',
        'source_model',
        'is_required',
        'is_unique',
        'is_searchable',
        'is_filterable',
        'is_visible',
        'default_value',
        'validation_rules',
        'sort_order',
        'note'
    ];

    protected array $casts = [
        'is_required' => 'boolean',
        'is_unique' => 'boolean',
        'is_searchable' => 'boolean',
        'is_filterable' => 'boolean',
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
        'validation_rules' => 'json'
    ];

    /**
     * Get the entity type this attribute belongs to
     */
    public function entityType()
    {
        return $this->belongsTo(EntityType::class, 'entity_type_id');
    }

    /**
     * Get attribute options (for select/multiselect)
     */
    public function options()
    {
        return $this->hasMany(AttributeOption::class, 'attribute_id');
    }

    /**
     * Check if attribute is required
     */
    public function isRequired(): bool
    {
        return (bool)$this->is_required;
    }

    /**
     * Check if attribute is unique
     */
    public function isUnique(): bool
    {
        return (bool)$this->is_unique;
    }

    /**
     * Check if attribute is searchable
     */
    public function isSearchable(): bool
    {
        return (bool)$this->is_searchable;
    }

    /**
     * Check if attribute is filterable
     */
    public function isFilterable(): bool
    {
        return (bool)$this->is_filterable;
    }

    /**
     * Get validation rules
     */
    public function getValidationRules(): array
    {
        if (is_string($this->validation_rules)) {
            return json_decode($this->validation_rules, true) ?? [];
        }
        return $this->validation_rules ?? [];
    }

    /**
     * Set validation rules
     */
    public function setValidationRules(array $rules): void
    {
        $this->validation_rules = $rules;
    }
}
