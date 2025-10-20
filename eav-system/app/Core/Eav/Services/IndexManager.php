<?php
// app/Eav/Services/IndexManager.php
namespace Eav\Services;

use Core\Database\Database;

/**
 * Index Manager
 * 
 * Manage value indexing for searchable attributes and optimize lookups
 */
class IndexManager
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Create index for searchable attribute
     */
    public function createAttributeIndex(int $attributeId, string $backendType): bool
    {
        $tableName = "eav_values_{$backendType}";
        $indexName = "idx_attr_{$attributeId}_value";

        try {
            // Check if index already exists
            if ($this->indexExists($tableName, $indexName)) {
                return true;
            }

            // Create index
            $sql = "CREATE INDEX {$indexName} ON {$tableName} (attribute_id, value)";
            $this->db->execute($sql, []);

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Drop index for attribute
     */
    public function dropAttributeIndex(int $attributeId, string $backendType): bool
    {
        $tableName = "eav_values_{$backendType}";
        $indexName = "idx_attr_{$attributeId}_value";

        try {
            if (!$this->indexExists($tableName, $indexName)) {
                return true;
            }

            $sql = "DROP INDEX {$indexName} ON {$tableName}";
            $this->db->execute($sql, []);

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if index exists
     */
    public function indexExists(string $tableName, string $indexName): bool
    {
        $sql = "SHOW INDEX FROM {$tableName} WHERE Key_name = ?";
        $result = $this->db->execute($sql, [$indexName]);
        
        return $result->rowCount() > 0;
    }

    /**
     * Rebuild all attribute indexes
     */
    public function rebuildIndexes(int $entityTypeId): bool
    {
        try {
            // Get all searchable attributes for entity type
            $attributes = $this->db->table('eav_attributes')
                ->where('entity_type_id', $entityTypeId)
                ->where('is_searchable', true)
                ->get();

            foreach ($attributes as $attribute) {
                $this->createAttributeIndex($attribute['id'], $attribute['backend_type']);
            }

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Optimize value tables
     */
    public function optimizeTables(): bool
    {
        $tables = [
            'eav_values_varchar',
            'eav_values_int',
            'eav_values_decimal',
            'eav_values_text',
            'eav_values_datetime'
        ];

        try {
            foreach ($tables as $table) {
                $sql = "OPTIMIZE TABLE {$table}";
                $this->db->execute($sql, []);
            }

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Analyze value tables for query optimization
     */
    public function analyzeTables(): bool
    {
        $tables = [
            'eav_values_varchar',
            'eav_values_int',
            'eav_values_decimal',
            'eav_values_text',
            'eav_values_datetime'
        ];

        try {
            foreach ($tables as $table) {
                $sql = "ANALYZE TABLE {$table}";
                $this->db->execute($sql, []);
            }

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get index statistics
     */
    public function getIndexStats(string $tableName): array
    {
        $sql = "SHOW INDEX FROM {$tableName}";
        $result = $this->db->execute($sql, []);
        
        return $result->fetchAll();
    }

    /**
     * Get table statistics
     */
    public function getTableStats(): array
    {
        $tables = [
            'eav_entities',
            'eav_attributes',
            'eav_values_varchar',
            'eav_values_int',
            'eav_values_decimal',
            'eav_values_text',
            'eav_values_datetime'
        ];

        $stats = [];

        foreach ($tables as $table) {
            $sql = "SELECT COUNT(*) as row_count FROM {$table}";
            $result = $this->db->execute($sql, [])->fetch();
            
            $stats[$table] = [
                'row_count' => $result['row_count'] ?? 0
            ];
        }

        return $stats;
    }

    /**
     * Create full-text index for text attributes
     */
    public function createFullTextIndex(int $attributeId): bool
    {
        $tableName = "eav_values_text";
        $indexName = "ft_attr_{$attributeId}";

        try {
            if ($this->indexExists($tableName, $indexName)) {
                return true;
            }

            $sql = "CREATE FULLTEXT INDEX {$indexName} ON {$tableName} (value)";
            $this->db->execute($sql, []);

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Clean up orphaned values (values without entities)
     */
    public function cleanOrphanedValues(): int
    {
        $tables = [
            'eav_values_varchar',
            'eav_values_int',
            'eav_values_decimal',
            'eav_values_text',
            'eav_values_datetime'
        ];

        $totalDeleted = 0;

        foreach ($tables as $table) {
            $sql = "DELETE v FROM {$table} v 
                    LEFT JOIN eav_entities e ON v.entity_id = e.id 
                    WHERE e.id IS NULL";
            
            $result = $this->db->execute($sql, []);
            $totalDeleted += $result->rowCount();
        }

        return $totalDeleted;
    }
}
