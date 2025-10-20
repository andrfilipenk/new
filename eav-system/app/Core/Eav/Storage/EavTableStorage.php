<?php
// app/Core/Eav/Storage/EavTableStorage.php
namespace Core\Eav\Storage;

use Core\Database\Database;
use Core\Eav\Model\Attribute;
use Core\Eav\Exception\StorageException;
use Core\Di\Injectable;

/**
 * EAV table storage strategy implementation
 */
class EavTableStorage implements StorageStrategyInterface
{
    use Injectable;

    private Database $db;
    private ValueTransformer $transformer;

    public function __construct(Database $db, ValueTransformer $transformer)
    {
        $this->db = $db;
        $this->transformer = $transformer;
    }

    /**
     * Load attribute values for an entity
     */
    public function loadValues(int $entityId, array $attributes): array
    {
        if (empty($attributes)) {
            return [];
        }

        // Group attributes by backend type
        $grouped = $this->groupByBackendType($attributes);
        $values = [];

        foreach ($grouped as $backendType => $attrs) {
            $attributeIds = array_map(fn($attr) => $attr->getAttributeId(), $attrs);
            $attributeMap = [];
            foreach ($attrs as $attr) {
                $attributeMap[$attr->getAttributeId()] = $attr;
            }

            // Query value table for this backend type
            $tableName = $this->getValueTable($backendType);
            $results = $this->db->table($tableName)
                ->whereIn('attribute_id', $attributeIds)
                ->where('entity_id', $entityId)
                ->get();

            // Map results back to attribute codes
            foreach ($results as $row) {
                $attributeId = (int) $row['attribute_id'];
                if (isset($attributeMap[$attributeId])) {
                    $attribute = $attributeMap[$attributeId];
                    $values[$attribute->getCode()] = $this->transformer->fromDatabase(
                        $row['value'],
                        $backendType
                    );
                }
            }
        }

        return $values;
    }

    /**
     * Save attribute values for an entity
     */
    public function saveValues(int $entityId, int $entityTypeId, array $values): bool
    {
        if (empty($values)) {
            return true;
        }

        try {
            // Group by backend type
            $grouped = $this->groupValuesByBackendType($values);

            foreach ($grouped as $backendType => $items) {
                $this->saveValuesToTable($entityId, $entityTypeId, $backendType, $items);
            }

            return true;

        } catch (\Exception $e) {
            throw new StorageException(
                "Failed to save values: " . $e->getMessage(),
                "Value save failed",
                ['entity_id' => $entityId, 'error' => $e->getMessage()]
            );
        }
    }

    /**
     * Delete specific attribute values
     */
    public function deleteValues(int $entityId, array $attributeCodes): bool
    {
        // This requires attribute metadata to determine backend types
        // For now, delete from all value tables
        foreach (['varchar', 'int', 'decimal', 'datetime', 'text'] as $type) {
            $this->db->table($this->getValueTable($type))
                ->where('entity_id', $entityId)
                ->delete();
        }

        return true;
    }

    /**
     * Delete all attribute values for an entity
     */
    public function deleteAllValues(int $entityId): bool
    {
        try {
            foreach (['varchar', 'int', 'decimal', 'datetime', 'text'] as $type) {
                $this->db->table($this->getValueTable($type))
                    ->where('entity_id', $entityId)
                    ->delete();
            }

            return true;

        } catch (\Exception $e) {
            throw new StorageException(
                "Failed to delete values: " . $e->getMessage(),
                "Value deletion failed",
                ['entity_id' => $entityId, 'error' => $e->getMessage()]
            );
        }
    }

    /**
     * Load values for multiple entities
     */
    public function loadMultiple(array $entityIds, array $attributes): array
    {
        if (empty($entityIds) || empty($attributes)) {
            return [];
        }

        // Group attributes by backend type
        $grouped = $this->groupByBackendType($attributes);
        $allValues = [];

        // Initialize result structure
        foreach ($entityIds as $entityId) {
            $allValues[$entityId] = [];
        }

        foreach ($grouped as $backendType => $attrs) {
            $attributeIds = array_map(fn($attr) => $attr->getAttributeId(), $attrs);
            $attributeMap = [];
            foreach ($attrs as $attr) {
                $attributeMap[$attr->getAttributeId()] = $attr;
            }

            // Query value table for this backend type
            $tableName = $this->getValueTable($backendType);
            $results = $this->db->table($tableName)
                ->whereIn('attribute_id', $attributeIds)
                ->whereIn('entity_id', $entityIds)
                ->get();

            // Map results back to entity => attribute code => value
            foreach ($results as $row) {
                $entityId = (int) $row['entity_id'];
                $attributeId = (int) $row['attribute_id'];
                
                if (isset($attributeMap[$attributeId]) && isset($allValues[$entityId])) {
                    $attribute = $attributeMap[$attributeId];
                    $allValues[$entityId][$attribute->getCode()] = $this->transformer->fromDatabase(
                        $row['value'],
                        $backendType
                    );
                }
            }
        }

        return $allValues;
    }

    /**
     * Get table name for backend type
     */
    public function getValueTable(string $backendType): string
    {
        return "eav_value_{$backendType}";
    }

    /**
     * Group attributes by backend type
     */
    private function groupByBackendType(array $attributes): array
    {
        $grouped = [];
        foreach ($attributes as $attribute) {
            $type = $attribute->getBackendType();
            $grouped[$type][] = $attribute;
        }
        return $grouped;
    }

    /**
     * Group values by backend type
     */
    private function groupValuesByBackendType(array $values): array
    {
        $grouped = [];
        foreach ($values as $attribute => $value) {
            if (!($attribute instanceof Attribute)) {
                continue;
            }
            $type = $attribute->getBackendType();
            $grouped[$type][] = [
                'attribute' => $attribute,
                'value' => $value
            ];
        }
        return $grouped;
    }

    /**
     * Save values to specific backend table
     */
    private function saveValuesToTable(
        int $entityId,
        int $entityTypeId,
        string $backendType,
        array $items
    ): void {
        $tableName = $this->getValueTable($backendType);

        foreach ($items as $item) {
            $attribute = $item['attribute'];
            $value = $item['value'];

            // Transform value
            $dbValue = $this->transformer->toDatabase($value, $backendType);

            // Use REPLACE INTO for upsert
            $sql = "REPLACE INTO {$tableName} (entity_type_id, attribute_id, entity_id, value) VALUES (?, ?, ?, ?)";
            
            $this->db->execute($sql, [
                $entityTypeId,
                $attribute->getAttributeId(),
                $entityId,
                $dbValue
            ]);
        }
    }
}
