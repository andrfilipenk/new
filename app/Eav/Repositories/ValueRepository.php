<?php
// app/Eav/Repositories/ValueRepository.php
namespace Eav\Repositories;

use Core\Database\Database;
use Eav\Models\Attribute;
use Eav\Storage\StorageStrategyFactory;
use Eav\Storage\StorageStrategyInterface;

/**
 * Value Repository
 * 
 * Handles EAV value storage and retrieval across different value type tables
 */
class ValueRepository
{
    private Database $db;
    private StorageStrategyFactory $storageFactory;

    public function __construct(Database $db, StorageStrategyFactory $storageFactory)
    {
        $this->db = $db;
        $this->storageFactory = $storageFactory;
    }

    /**
     * Get a single value
     */
    public function getValue(int $entityId, Attribute $attribute): mixed
    {
        $strategy = $this->getStrategy($attribute->backend_type);
        return $strategy->getValue($entityId, $attribute->id);
    }

    /**
     * Save a single value
     */
    public function saveValue(int $entityId, Attribute $attribute, mixed $value): bool
    {
        $strategy = $this->getStrategy($attribute->backend_type);
        return $strategy->saveValue($entityId, $attribute->id, $value);
    }

    /**
     * Delete a single value
     */
    public function deleteValue(int $entityId, Attribute $attribute): bool
    {
        $strategy = $this->getStrategy($attribute->backend_type);
        return $strategy->deleteValue($entityId, $attribute->id);
    }

    /**
     * Get all values for an entity
     */
    public function getEntityValues(int $entityId, array $attributes): array
    {
        $values = [];

        // Group attributes by backend type for efficient queries
        $attributesByType = $this->groupAttributesByType($attributes);

        foreach ($attributesByType as $backendType => $typeAttributes) {
            $strategy = $this->getStrategy($backendType);
            $attributeIds = array_map(fn($attr) => $attr->id, $typeAttributes);
            
            $typeValues = $strategy->getEntityValues($entityId, $attributeIds);

            // Map back to attribute codes
            foreach ($typeAttributes as $attribute) {
                if (isset($typeValues[$attribute->id])) {
                    $values[$attribute->attribute_code] = $typeValues[$attribute->id];
                }
            }
        }

        return $values;
    }

    /**
     * Save multiple values for an entity
     */
    public function saveEntityValues(int $entityId, array $attributeValues, array $attributes): bool
    {
        // Create attribute lookup by code
        $attributeMap = [];
        foreach ($attributes as $attribute) {
            $attributeMap[$attribute->attribute_code] = $attribute;
        }

        // Group values by backend type
        $valuesByType = [];
        foreach ($attributeValues as $code => $value) {
            if (!isset($attributeMap[$code])) {
                continue;
            }

            $attribute = $attributeMap[$code];
            $backendType = $attribute->backend_type;

            if (!isset($valuesByType[$backendType])) {
                $valuesByType[$backendType] = [];
            }

            $valuesByType[$backendType][$attribute->id] = $value;
        }

        // Save values by type
        $success = true;
        foreach ($valuesByType as $backendType => $values) {
            $strategy = $this->getStrategy($backendType);
            if (!$strategy->saveEntityValues($entityId, $values)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Delete all values for an entity
     */
    public function deleteEntityValues(int $entityId): bool
    {
        $success = true;

        // Delete from all value tables
        foreach ($this->storageFactory->getAvailableTypes() as $type) {
            $strategy = $this->getStrategy($type);
            $tableName = $strategy->getTableName();
            
            $affected = $this->db->table($tableName)
                ->where('entity_id', $entityId)
                ->delete();

            if ($affected === false) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Get values for multiple entities
     */
    public function getMultipleEntityValues(array $entityIds, array $attributes): array
    {
        $results = [];

        // Group attributes by backend type
        $attributesByType = $this->groupAttributesByType($attributes);

        foreach ($attributesByType as $backendType => $typeAttributes) {
            $strategy = $this->getStrategy($backendType);
            $attributeIds = array_map(fn($attr) => $attr->id, $typeAttributes);
            $tableName = $strategy->getTableName();

            // Query values for all entities at once
            $query = $this->db->table($tableName)
                ->whereIn('entity_id', $entityIds);

            if (!empty($attributeIds)) {
                $query->whereIn('attribute_id', $attributeIds);
            }

            $rows = $query->get();

            // Organize results by entity ID
            foreach ($rows as $row) {
                $entityId = $row['entity_id'];
                $attributeId = $row['attribute_id'];

                // Find attribute by ID
                $attribute = null;
                foreach ($typeAttributes as $attr) {
                    if ($attr->id == $attributeId) {
                        $attribute = $attr;
                        break;
                    }
                }

                if ($attribute) {
                    if (!isset($results[$entityId])) {
                        $results[$entityId] = [];
                    }

                    $results[$entityId][$attribute->attribute_code] = 
                        $strategy->transformFromStorage($row['value']);
                }
            }
        }

        return $results;
    }

    /**
     * Copy values from one entity to another
     */
    public function copyEntityValues(int $sourceEntityId, int $targetEntityId, array $attributes): bool
    {
        $sourceValues = $this->getEntityValues($sourceEntityId, $attributes);
        return $this->saveEntityValues($targetEntityId, $sourceValues, $attributes);
    }

    /**
     * Get unique values for an attribute
     */
    public function getUniqueValues(Attribute $attribute, int $limit = 100): array
    {
        $strategy = $this->getStrategy($attribute->backend_type);
        $tableName = $strategy->getTableName();

        $results = $this->db->table($tableName)
            ->where('attribute_id', $attribute->id)
            ->select(['value'])
            ->groupBy('value')
            ->limit($limit)
            ->get();

        return array_map(
            fn($row) => $strategy->transformFromStorage($row['value']),
            $results
        );
    }

    /**
     * Count entities with a specific attribute value
     */
    public function countByValue(Attribute $attribute, mixed $value): int
    {
        $strategy = $this->getStrategy($attribute->backend_type);
        $tableName = $strategy->getTableName();
        $transformedValue = $strategy->transformForStorage($value);

        $result = $this->db->table($tableName)
            ->where('attribute_id', $attribute->id)
            ->where('value', $transformedValue)
            ->selectRaw('COUNT(*) as count')
            ->first();

        return $result['count'] ?? 0;
    }

    /**
     * Search entities by attribute value
     */
    public function searchByValue(Attribute $attribute, string $searchTerm, int $limit = 100): array
    {
        $strategy = $this->getStrategy($attribute->backend_type);
        $tableName = $strategy->getTableName();

        $query = $this->db->table($tableName)
            ->where('attribute_id', $attribute->id)
            ->select(['entity_id', 'value'])
            ->limit($limit);

        // Add search condition based on backend type
        if ($attribute->backend_type === 'varchar' || $attribute->backend_type === 'text') {
            $query->whereRaw('value LIKE ?', ['%' . $searchTerm . '%']);
        } else {
            $query->where('value', $searchTerm);
        }

        $results = $query->get();

        return array_map(function($row) use ($strategy) {
            return [
                'entity_id' => $row['entity_id'],
                'value' => $strategy->transformFromStorage($row['value'])
            ];
        }, $results);
    }

    /**
     * Get storage strategy for a backend type
     */
    private function getStrategy(string $backendType): StorageStrategyInterface
    {
        return $this->storageFactory->getStrategy($backendType);
    }

    /**
     * Group attributes by backend type
     */
    private function groupAttributesByType(array $attributes): array
    {
        $grouped = [];
        foreach ($attributes as $attribute) {
            $type = $attribute->backend_type;
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = $attribute;
        }
        return $grouped;
    }

    /**
     * Batch update values
     */
    public function batchUpdateValues(array $updates): bool
    {
        // Group updates by backend type
        $updatesByType = [];
        
        foreach ($updates as $update) {
            $entityId = $update['entity_id'];
            $attribute = $update['attribute'];
            $value = $update['value'];
            $backendType = $attribute->backend_type;

            if (!isset($updatesByType[$backendType])) {
                $updatesByType[$backendType] = [];
            }

            $updatesByType[$backendType][] = [
                'entity_id' => $entityId,
                'attribute_id' => $attribute->id,
                'value' => $value
            ];
        }

        // Execute batch updates for each type
        $success = true;
        foreach ($updatesByType as $backendType => $typeUpdates) {
            $strategy = $this->getStrategy($backendType);
            
            foreach ($typeUpdates as $update) {
                if (!$strategy->saveValue($update['entity_id'], $update['attribute_id'], $update['value'])) {
                    $success = false;
                }
            }
        }

        return $success;
    }
}
