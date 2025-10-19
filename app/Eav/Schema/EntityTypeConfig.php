<?php

namespace App\Eav\Schema;

/**
 * Entity Type Configuration
 * 
 * Normalized representation of entity type configuration from config files.
 */
class EntityTypeConfig
{
    private string $entityTypeCode;
    private string $entityTable;
    private array $attributes;
    private array $valueTablesConfig;

    public function __construct(
        string $entityTypeCode,
        string $entityTable,
        array $attributes,
        array $valueTablesConfig = []
    ) {
        $this->entityTypeCode = $entityTypeCode;
        $this->entityTable = $entityTable;
        $this->attributes = $attributes;
        $this->valueTablesConfig = $valueTablesConfig;
    }

    public function getEntityTypeCode(): string
    {
        return $this->entityTypeCode;
    }

    public function getEntityTable(): string
    {
        return $this->entityTable;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $attributeCode): ?array
    {
        return $this->attributes[$attributeCode] ?? null;
    }

    public function getValueTablesConfig(): array
    {
        return $this->valueTablesConfig;
    }

    public function getAttributesByBackendType(string $backendType): array
    {
        return array_filter(
            $this->attributes,
            fn($attr) => ($attr['backend_type'] ?? 'varchar') === $backendType
        );
    }

    public function getSearchableAttributes(): array
    {
        return array_filter(
            $this->attributes,
            fn($attr) => !empty($attr['is_searchable'])
        );
    }

    public function getFilterableAttributes(): array
    {
        return array_filter(
            $this->attributes,
            fn($attr) => !empty($attr['is_filterable'])
        );
    }

    public function getRequiredAttributes(): array
    {
        return array_filter(
            $this->attributes,
            fn($attr) => !empty($attr['is_required'])
        );
    }

    public function toArray(): array
    {
        return [
            'entity_type_code' => $this->entityTypeCode,
            'entity_table' => $this->entityTable,
            'attributes' => $this->attributes,
            'value_tables_config' => $this->valueTablesConfig,
        ];
    }
}
