<?php
// app/Eav/Storage/StorageStrategyFactory.php
namespace Eav\Storage;

use Core\Database\Database;
use InvalidArgumentException;

/**
 * Storage Strategy Factory
 * 
 * Creates appropriate storage strategy instances based on backend type
 */
class StorageStrategyFactory
{
    private Database $db;
    private array $strategies = [];
    private array $tableMapping;

    public function __construct(Database $db)
    {
        $this->db = $db;
        
        // Default table mapping
        $this->tableMapping = [
            'varchar' => 'eav_values_varchar',
            'int' => 'eav_values_int',
            'decimal' => 'eav_values_decimal',
            'text' => 'eav_values_text',
            'datetime' => 'eav_values_datetime',
        ];
    }

    /**
     * Get storage strategy for a backend type
     */
    public function getStrategy(string $backendType): StorageStrategyInterface
    {
        if (isset($this->strategies[$backendType])) {
            return $this->strategies[$backendType];
        }

        $strategy = $this->createStrategy($backendType);
        $this->strategies[$backendType] = $strategy;

        return $strategy;
    }

    /**
     * Create a new storage strategy instance
     */
    private function createStrategy(string $backendType): StorageStrategyInterface
    {
        if (!isset($this->tableMapping[$backendType])) {
            throw new InvalidArgumentException("Unknown backend type: {$backendType}");
        }

        $tableName = $this->tableMapping[$backendType];

        return match($backendType) {
            'varchar' => new VarcharStorageStrategy($this->db, $tableName),
            'int' => new IntStorageStrategy($this->db, $tableName),
            'decimal' => new DecimalStorageStrategy($this->db, $tableName),
            'text' => new TextStorageStrategy($this->db, $tableName),
            'datetime' => new DatetimeStorageStrategy($this->db, $tableName),
            default => throw new InvalidArgumentException("Unsupported backend type: {$backendType}")
        };
    }

    /**
     * Set custom table mapping
     */
    public function setTableMapping(array $mapping): void
    {
        $this->tableMapping = array_merge($this->tableMapping, $mapping);
    }

    /**
     * Get all available backend types
     */
    public function getAvailableTypes(): array
    {
        return array_keys($this->tableMapping);
    }

    /**
     * Clear cached strategies
     */
    public function clearCache(): void
    {
        $this->strategies = [];
    }
}
