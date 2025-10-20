<?php
// app/Core/Eav/Entity/Attribute.php
namespace Core\Eav\Entity;

/**
 * Attribute Metadata Class
 * 
 * Represents attribute configuration and properties
 */
class Attribute
{
    private string $code;
    private string $label;
    private string $type;
    private bool $required;
    private bool $unique;
    private bool $searchable;
    private bool $filterable;
    private mixed $default;
    private array $config;

    public function __construct(string $code, array $config = [])
    {
        $this->code = $code;
        $this->config = $config;
        $this->label = $config['label'] ?? ucfirst($code);
        $this->type = $config['type'] ?? 'varchar';
        $this->required = $config['required'] ?? false;
        $this->unique = $config['unique'] ?? false;
        $this->searchable = $config['searchable'] ?? false;
        $this->filterable = $config['filterable'] ?? false;
        $this->default = $config['default'] ?? null;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function isUnique(): bool
    {
        return $this->unique;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function isFilterable(): bool
    {
        return $this->filterable;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    /**
     * Get backend table for this attribute type
     */
    public function getBackendTable(): string
    {
        return match($this->type) {
            'int' => 'eav_entity_int',
            'decimal' => 'eav_entity_decimal',
            'datetime' => 'eav_entity_datetime',
            'text' => 'eav_entity_text',
            default => 'eav_entity_varchar',
        };
    }

    /**
     * Get all config
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'label' => $this->label,
            'type' => $this->type,
            'required' => $this->required,
            'unique' => $this->unique,
            'searchable' => $this->searchable,
            'filterable' => $this->filterable,
            'default' => $this->default,
        ];
    }
}
