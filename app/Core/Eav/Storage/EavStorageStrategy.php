<?php
// app/Core/Eav/Storage/EavStorageStrategy.php
namespace Core\Eav\Storage;

use Core\Eav\Entity\Entity;
use Core\Eav\Entity\EntityType;
use Core\Eav\Entity\Attribute;
use Core\Database\Database;
use Core\Di\Injectable;

/**
 * EAV Storage Strategy
 * 
 * Handles entity storage using traditional EAV table structure
 */
class EavStorageStrategy implements StorageStrategy
{
    use Injectable;

    private Database $db;
    private array $config;
    private array $entityTypes = [];

    public function __construct()
    {
        $this->db = $this->getDI()->get('db');
        $this->config = require APP_PATH . 'Core/Eav/config.php';
    }

    /**
     * Load entity by ID
     */
    public function load(string $entityType, int $id): ?Entity
    {
        $entities = $this->loadMultiple($entityType, [$id]);
        return $entities[0] ?? null;
    }

    /**
     * Load multiple entities by IDs
     */
    public function loadMultiple(string $entityType, array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $entityTypeObj = $this->getEntityType($entityType);
        $tableName = $this->config['tables']['entity'];

        // Load entity records
        $entityRecords = $this->db->table($tableName)
            ->whereIn('entity_id', $ids)
            ->where('entity_type', $entityType)
            ->get();

        if (empty($entityRecords)) {
            return [];
        }

        $entities = [];
        foreach ($entityRecords as $record) {
            $entity = new Entity($entityType, $record['entity_id']);
            $entity->setCreatedAt($record['created_at'] ?? null);
            $entity->setUpdatedAt($record['updated_at'] ?? null);
            $entities[$record['entity_id']] = $entity;
        }

        // Load attributes for all entities
        $this->loadAttributeValues($entityTypeObj, $entities);

        return array_values($entities);
    }

    /**
     * Load attribute values for entities
     */
    private function loadAttributeValues(EntityType $entityType, array &$entities): void
    {
        if (empty($entities)) {
            return;
        }

        $entityIds = array_keys($entities);
        $attributes = $entityType->getAttributes();

        // Group attributes by backend table
        $attributesByTable = [];
        foreach ($attributes as $code => $attrConfig) {
            $attribute = new Attribute($code, $attrConfig);
            $table = $attribute->getBackendTable();
            $attributesByTable[$table][] = $code;
        }

        // Load from each backend table
        foreach ($attributesByTable as $table => $attrCodes) {
            $values = $this->db->table($table)
                ->whereIn('entity_id', $entityIds)
                ->get();

            foreach ($values as $value) {
                $entityId = $value['entity_id'];
                $attrCode = $value['attribute_code'] ?? null;
                
                if ($attrCode && isset($entities[$entityId])) {
                    $entities[$entityId]->setAttribute($attrCode, $value['value']);
                }
            }
        }

        // Reset dirty tracking after loading
        foreach ($entities as $entity) {
            $entity->resetDirtyTracking();
        }
    }

    /**
     * Save entity
     */
    public function save(Entity $entity): bool
    {
        $this->db->beginTransaction();

        try {
            $entityType = $this->getEntityType($entity->getEntityType());
            $tableName = $this->config['tables']['entity'];

            if ($entity->isNew()) {
                // Insert new entity
                $id = $this->db->table($tableName)->insert([
                    'entity_type' => $entity->getEntityType(),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $entity->setId((int)$id);

                // Insert all attributes
                $this->saveAttributeValues($entity, $entity->getAttributes());
            } else {
                // Update existing entity
                $this->db->table($tableName)
                    ->where('entity_id', $entity->getId())
                    ->update(['updated_at' => date('Y-m-d H:i:s')]);

                // Update only dirty attributes
                if ($entity->isDirty()) {
                    $this->saveAttributeValues($entity, $entity->getDirtyAttributes());
                }
            }

            $entity->resetDirtyTracking();
            $this->db->commit();
            return true;

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Save attribute values
     */
    private function saveAttributeValues(Entity $entity, array $attributes): void
    {
        if (empty($attributes)) {
            return;
        }

        $entityType = $this->getEntityType($entity->getEntityType());

        foreach ($attributes as $code => $value) {
            $attrConfig = $entityType->getAttribute($code);
            if (!$attrConfig) {
                continue;
            }

            $table = $attrConfig->getBackendTable();

            // Check if value exists
            $existing = $this->db->table($table)
                ->where('entity_id', $entity->getId())
                ->where('attribute_code', $code)
                ->first();

            if ($existing) {
                // Update
                $this->db->table($table)
                    ->where('entity_id', $entity->getId())
                    ->where('attribute_code', $code)
                    ->update(['value' => $value]);
            } else {
                // Insert
                $this->db->table($table)->insert([
                    'entity_id' => $entity->getId(),
                    'attribute_code' => $code,
                    'value' => $value,
                ]);
            }
        }
    }

    /**
     * Delete entity
     */
    public function delete(Entity $entity): bool
    {
        if ($entity->isNew()) {
            return false;
        }

        $this->db->beginTransaction();

        try {
            $entityType = $this->getEntityType($entity->getEntityType());

            // Delete attribute values from all backend tables
            $attributes = $entityType->getAttributes();
            $tables = [];
            foreach ($attributes as $code => $attrConfig) {
                $attribute = new Attribute($code, $attrConfig);
                $tables[$attribute->getBackendTable()] = true;
            }

            foreach (array_keys($tables) as $table) {
                $this->db->table($table)
                    ->where('entity_id', $entity->getId())
                    ->delete();
            }

            // Delete entity record
            $tableName = $this->config['tables']['entity'];
            $this->db->table($tableName)
                ->where('entity_id', $entity->getId())
                ->delete();

            $this->db->commit();
            return true;

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Execute query
     */
    public function query(
        string $entityType,
        array $filters = [],
        array $sorts = [],
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $entityTypeObj = $this->getEntityType($entityType);
        $tableName = $this->config['tables']['entity'];

        // Start with entity table
        $query = $this->db->table($tableName)
            ->where('entity_type', $entityType);

        // Apply filters (simplified - full implementation would join attribute tables)
        foreach ($filters as $filter) {
            $attributeCode = $filter['attribute'] ?? null;
            $operator = $filter['operator'] ?? '=';
            $value = $filter['value'] ?? null;

            if ($attributeCode && $value !== null) {
                $attribute = $entityTypeObj->getAttribute($attributeCode);
                if ($attribute) {
                    // This is simplified - full implementation needs joins
                    // For now, load all and filter in memory
                }
            }
        }

        // Apply limit and offset
        if ($limit !== null) {
            $query->limit($limit);
        }
        if ($offset !== null) {
            $query->offset($offset);
        }

        $records = $query->get();
        $ids = array_column($records, 'entity_id');

        return $this->loadMultiple($entityType, $ids);
    }

    /**
     * Count entities
     */
    public function count(string $entityType, array $filters = []): int
    {
        $tableName = $this->config['tables']['entity'];
        
        return $this->db->table($tableName)
            ->where('entity_type', $entityType)
            ->count();
    }

    /**
     * Check if storage is available
     */
    public function isAvailable(): bool
    {
        try {
            $tableName = $this->config['tables']['entity'];
            $this->db->table($tableName)->limit(1)->get();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get entity type object
     */
    private function getEntityType(string $code): EntityType
    {
        if (!isset($this->entityTypes[$code])) {
            if (!isset($this->config['entity_types'][$code])) {
                throw new \RuntimeException("Entity type '$code' not found");
            }
            $this->entityTypes[$code] = new EntityType(
                $code,
                $this->config['entity_types'][$code]
            );
        }
        return $this->entityTypes[$code];
    }
}
