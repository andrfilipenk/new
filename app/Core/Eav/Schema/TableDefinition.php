<?php
// app/Core/Eav/Schema/TableDefinition.php
namespace Core\Eav\Schema;

/**
 * Represents table definition metadata
 */
class TableDefinition
{
    private string $tableName;
    private string $type; // 'entity', 'attribute', 'value', 'entity_type'
    private array $columns = [];
    private array $indexes = [];
    private array $foreignKeys = [];

    public function __construct(string $tableName, string $type)
    {
        $this->tableName = $tableName;
        $this->type = $type;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function addColumn(string $name, array $definition): void
    {
        $this->columns[$name] = $definition;
    }

    public function addIndex(string $name, array $columns, string $type = 'INDEX'): void
    {
        $this->indexes[$name] = [
            'columns' => $columns,
            'type' => $type
        ];
    }

    public function addForeignKey(string $name, array $definition): void
    {
        $this->foreignKeys[$name] = $definition;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getIndexes(): array
    {
        return $this->indexes;
    }

    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }
}
