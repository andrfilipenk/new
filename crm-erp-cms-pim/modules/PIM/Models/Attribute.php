<?php

namespace Modules\PIM\Models;

use Core\ORM\Model;

class Attribute extends Model
{
    protected string $table = 'pim_attributes';
    
    protected array $fillable = [
        'name', 'code', 'type', 'description', 'is_required', 'is_unique',
        'is_searchable', 'is_filterable', 'is_visible', 'is_system',
        'validation_rules', 'options', 'default_value', 'order', 'group'
    ];
    
    protected array $casts = [
        'is_required' => 'boolean',
        'is_unique' => 'boolean',
        'is_searchable' => 'boolean',
        'is_filterable' => 'boolean',
        'is_visible' => 'boolean',
        'is_system' => 'boolean',
        'order' => 'integer'
    ];
    
    /**
     * Get validation rules as array
     */
    public function getValidationRules(): array
    {
        if (empty($this->validation_rules)) {
            return [];
        }
        return json_decode($this->validation_rules, true) ?? [];
    }
    
    /**
     * Get options as array (for select/multiselect)
     */
    public function getOptions(): array
    {
        if (empty($this->options)) {
            return [];
        }
        return json_decode($this->options, true) ?? [];
    }
    
    /**
     * Set options from array
     */
    public function setOptions(array $options): void
    {
        $this->options = json_encode($options);
    }
    
    /**
     * Check if attribute is select type
     */
    public function isSelectType(): bool
    {
        return in_array($this->type, ['select', 'multiselect']);
    }
    
    /**
     * Check if attribute is numeric
     */
    public function isNumericType(): bool
    {
        return in_array($this->type, ['number', 'decimal']);
    }
    
    /**
     * Get value column name based on type
     */
    public function getValueColumn(): string
    {
        return match($this->type) {
            'textarea' => 'value_text',
            'number', 'decimal' => 'value_number',
            'date' => 'value_date',
            'boolean' => 'value_boolean',
            default => 'value'
        };
    }
}
