<?php
// app/Eav/Storage/AbstractStorageStrategy.php
namespace Eav\Storage;

use Core\Database\Database;

/**
 * Abstract Storage Strategy
 * 
 * Base implementation for type-specific storage handlers
 */
abstract class AbstractStorageStrategy implements StorageStrategyInterface
{
    protected Database $db;
    protected string $tableName;
    protected string $backendType;

    public function __construct(Database $db, string $tableName, string $backendType)
    {
        $this->db = $db;
        $this->tableName = $tableName;
        $this->backendType = $backendType;
    }

    public function getBackendType(): string
    {
        return $this->backendType;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function saveValue(int $entityId, int $attributeId, mixed $value): bool
    {
        if (!$this->validateValue($value)) {
            return false;
        }

        $transformedValue = $this->transformForStorage($value);

        // Check if value exists
        $existing = $this->db->table($this->tableName)
            ->where('entity_id', $entityId)
            ->where('attribute_id', $attributeId)
            ->first();

        if ($existing) {
            // Update existing value
            $affected = $this->db->table($this->tableName)
                ->where('entity_id', $entityId)
                ->where('attribute_id', $attributeId)
                ->update(['value' => $transformedValue]);
            return $affected > 0;
        } else {
            // Insert new value
            $id = $this->db->table($this->tableName)->insert([
                'entity_id' => $entityId,
                'attribute_id' => $attributeId,
                'value' => $transformedValue
            ]);
            return $id > 0;
        }
    }

    public function getValue(int $entityId, int $attributeId): mixed
    {
        $result = $this->db->table($this->tableName)
            ->where('entity_id', $entityId)
            ->where('attribute_id', $attributeId)
            ->first();

        if (!$result) {
            return null;
        }

        return $this->transformFromStorage($result['value']);
    }

    public function deleteValue(int $entityId, int $attributeId): bool
    {
        $affected = $this->db->table($this->tableName)
            ->where('entity_id', $entityId)
            ->where('attribute_id', $attributeId)
            ->delete();

        return $affected > 0;
    }

    public function getEntityValues(int $entityId, array $attributeIds = []): array
    {
        $query = $this->db->table($this->tableName)
            ->where('entity_id', $entityId);

        if (!empty($attributeIds)) {
            $query->whereIn('attribute_id', $attributeIds);
        }

        $results = $query->get();

        $values = [];
        foreach ($results as $row) {
            $values[$row['attribute_id']] = $this->transformFromStorage($row['value']);
        }

        return $values;
    }

    public function saveEntityValues(int $entityId, array $values): bool
    {
        $success = true;
        foreach ($values as $attributeId => $value) {
            if (!$this->saveValue($entityId, $attributeId, $value)) {
                $success = false;
            }
        }
        return $success;
    }

    public function validateValue(mixed $value): bool
    {
        return $value !== null;
    }

    public function transformForStorage(mixed $value): mixed
    {
        return $value;
    }

    public function transformFromStorage(mixed $value): mixed
    {
        return $value;
    }
}
