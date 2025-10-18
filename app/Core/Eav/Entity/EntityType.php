<?php
// app/Core/Eav/Entity/EntityType.php
namespace Core\Eav\Entity;

/**
 * Entity Type Definition
 * 
 * Represents the configuration and metadata for an entity type in the EAV system
 */
class EntityType
{
    private string $code;
    private string $label;
    private string $table;
    private array $attributes = [];
    private array $performance = [];

    public function __construct(string $code, array $config = [])
    {
        $this->code = $code;
        $this->label = $config['label'] ?? ucfirst($code);
        $this->table = $config['table'] ?? 'eav_entity';
        $this->attributes = $config['attributes'] ?? [];
        $this->performance = $config['performance'] ?? [];
    }

    /**
     * Get entity type code
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Get entity type label
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Get table name
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get all attributes
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Get attribute by code
     */
    public function getAttribute(string $code): ?Attribute
    {
        if (!isset($this->attributes[$code])) {
            return null;
        }

        return new Attribute($code, $this->attributes[$code]);
    }

    /**
     * Check if attribute exists
     */
    public function hasAttribute(string $code): bool
    {
        return isset($this->attributes[$code]);
    }

    /**
     * Get performance configuration
     */
    public function getPerformanceConfig(): array
    {
        return $this->performance;
    }

    /**
     * Get cache TTL
     */
    public function getCacheTtl(): ?int
    {
        return $this->performance['cache_ttl'] ?? null;
    }

    /**
     * Check if flat table is enabled
     */
    public function isFlatTableEnabled(): bool
    {
        return $this->performance['enable_flat_table'] ?? false;
    }

    /**
     * Get flat table sync mode
     */
    public function getFlatTableSyncMode(): string
    {
        return $this->performance['flat_table_sync_mode'] ?? 'immediate';
    }

    /**
     * Get cache priority
     */
    public function getCachePriority(): string
    {
        return $this->performance['cache_priority'] ?? 'normal';
    }

    /**
     * Check if query cache is enabled
     */
    public function isQueryCacheEnabled(): bool
    {
        return $this->performance['query_cache_enable'] ?? true;
    }

    /**
     * Get required attributes
     */
    public function getRequiredAttributes(): array
    {
        return array_filter($this->attributes, fn($attr) => $attr['required'] ?? false);
    }

    /**
     * Get searchable attributes
     */
    public function getSearchableAttributes(): array
    {
        return array_filter($this->attributes, fn($attr) => $attr['searchable'] ?? false);
    }

    /**
     * Get filterable attributes
     */
    public function getFilterableAttributes(): array
    {
        return array_filter($this->attributes, fn($attr) => $attr['filterable'] ?? false);
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'label' => $this->label,
            'table' => $this->table,
            'attributes' => $this->attributes,
            'performance' => $this->performance,
        ];
    }
}
