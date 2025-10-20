<?php

namespace App\Eav\Schema;

/**
 * Database Schema Representation
 * 
 * Normalized representation of the physical database schema.
 */
class DatabaseSchema
{
    private array $tables = [];
    private string $entityTypeCode;

    public function __construct(string $entityTypeCode)
    {
        $this->entityTypeCode = $entityTypeCode;
    }

    public function getEntityTypeCode(): string
    {
        return $this->entityTypeCode;
    }

    public function addTable(string $tableName, array $definition): void
    {
        $this->tables[$tableName] = $definition;
    }

    public function hasTable(string $tableName): bool
    {
        return isset($this->tables[$tableName]);
    }

    public function getTable(string $tableName): ?array
    {
        return $this->tables[$tableName] ?? null;
    }

    public function getTables(): array
    {
        return $this->tables;
    }

    public function getTableNames(): array
    {
        return array_keys($this->tables);
    }

    public function hasColumn(string $tableName, string $columnName): bool
    {
        return isset($this->tables[$tableName]['columns'][$columnName]);
    }

    public function getColumn(string $tableName, string $columnName): ?array
    {
        return $this->tables[$tableName]['columns'][$columnName] ?? null;
    }

    public function getColumns(string $tableName): array
    {
        return $this->tables[$tableName]['columns'] ?? [];
    }

    public function getIndexes(string $tableName): array
    {
        return $this->tables[$tableName]['indexes'] ?? [];
    }

    public function hasIndex(string $tableName, string $indexName): bool
    {
        return isset($this->tables[$tableName]['indexes'][$indexName]);
    }

    public function toArray(): array
    {
        return [
            'entity_type_code' => $this->entityTypeCode,
            'tables' => $this->tables,
        ];
    }
}
