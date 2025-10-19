<?php
// app/Core/Eav/Manager/EntityManager.php
namespace Core\Eav\Manager;

use Core\Database\Database;
use Core\Eav\Model\Entity;
use Core\Eav\Model\EntityType;
use Core\Eav\Config\EntityTypeRegistry;
use Core\Eav\Exception\EntityException;
use Core\Exception\ValidationException;
use Core\Di\Injectable;

/**
 * Manages entity lifecycle (CRUD operations)
 */
class EntityManager
{
    use Injectable;

    private Database $db;
    private ValueManager $valueManager;
    private EntityTypeRegistry $registry;

    public function __construct(
        Database $db,
        ValueManager $valueManager,
        EntityTypeRegistry $registry
    ) {
        $this->db = $db;
        $this->valueManager = $valueManager;
        $this->registry = $registry;
    }

    /**
     * Create new entity instance
     */
    public function create(EntityType $entityType, array $data): Entity
    {
        // Create entity model
        $entity = new Entity($entityType);
        $entity->setDataValues($data);

        // Validate
        $errors = $entity->validate();
        if (!empty($errors)) {
            throw new ValidationException($errors, "Entity validation failed");
        }

        try {
            $this->db->beginTransaction();

            // Insert entity record
            $entityId = $this->db->table($entityType->getEntityTable())->insert([
                'entity_type_id' => $entityType->getEntityTypeId()
            ]);

            $entity->setId((int)$entityId);

            // Save attribute values
            $this->valueManager->saveValues(
                (int)$entityId,
                $entityType,
                $entity->getData()
            );

            $this->db->commit();

            // Clear dirty tracking
            $entity->clearDirtyTracking();

            return $entity;

        } catch (\Exception $e) {
            $this->db->rollback();
            throw new EntityException(
                "Failed to create entity: " . $e->getMessage(),
                "Entity creation failed",
                ['entity_type' => $entityType->getCode(), 'error' => $e->getMessage()]
            );
        }
    }

    /**
     * Load entity by ID
     */
    public function load(EntityType $entityType, int $id): ?Entity
    {
        // Load entity record
        $record = $this->db->table($entityType->getEntityTable())
            ->where('entity_id', $id)
            ->first();

        if (!$record) {
            return null;
        }

        // Create entity instance
        $entity = new Entity($entityType);
        $entity->setId($id);

        if (isset($record['created_at'])) {
            $entity->setCreatedAt($record['created_at']);
        }
        if (isset($record['updated_at'])) {
            $entity->setUpdatedAt($record['updated_at']);
        }

        // Load attribute values
        $values = $this->valueManager->loadValues($id, $entityType->getAttributes());
        $entity->setDataValues($values);

        // Clear dirty tracking (freshly loaded)
        $entity->clearDirtyTracking();

        return $entity;
    }

    /**
     * Save entity changes
     */
    public function save(Entity $entity): bool
    {
        if (!$entity->getId()) {
            throw new EntityException(
                "Cannot save entity without ID. Use create() for new entities.",
                "Invalid operation",
                ['entity_type' => $entity->getEntityType()->getCode()]
            );
        }

        // Skip if no changes
        if (!$entity->isDirty()) {
            return true;
        }

        // Validate
        $errors = $entity->validate();
        if (!empty($errors)) {
            throw new ValidationException($errors, "Entity validation failed");
        }

        try {
            $this->db->beginTransaction();

            $entityType = $entity->getEntityType();

            // Update entity table (timestamp will be auto-updated)
            $this->db->table($entityType->getEntityTable())
                ->where('entity_id', $entity->getId())
                ->update([]);

            // Save only dirty values
            $dirtyData = $entity->getDirtyData();
            if (!empty($dirtyData)) {
                $this->valueManager->saveValues(
                    $entity->getId(),
                    $entityType,
                    $dirtyData
                );
            }

            $this->db->commit();

            // Clear dirty tracking
            $entity->clearDirtyTracking();

            return true;

        } catch (\Exception $e) {
            $this->db->rollback();
            throw new EntityException(
                "Failed to save entity: " . $e->getMessage(),
                "Entity save failed",
                [
                    'entity_type' => $entity->getEntityType()->getCode(),
                    'entity_id' => $entity->getId(),
                    'error' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * Delete entity
     */
    public function delete(Entity $entity): bool
    {
        if (!$entity->getId()) {
            throw new EntityException(
                "Cannot delete entity without ID",
                "Invalid operation",
                ['entity_type' => $entity->getEntityType()->getCode()]
            );
        }

        try {
            $this->db->beginTransaction();

            $entityType = $entity->getEntityType();
            $entityId = $entity->getId();

            // Delete from all value tables
            foreach (['varchar', 'int', 'decimal', 'datetime', 'text'] as $type) {
                $this->db->table("eav_value_{$type}")
                    ->where('entity_id', $entityId)
                    ->delete();
            }

            // Delete entity record
            $this->db->table($entityType->getEntityTable())
                ->where('entity_id', $entityId)
                ->delete();

            $this->db->commit();

            return true;

        } catch (\Exception $e) {
            $this->db->rollback();
            throw new EntityException(
                "Failed to delete entity: " . $e->getMessage(),
                "Entity deletion failed",
                [
                    'entity_type' => $entity->getEntityType()->getCode(),
                    'entity_id' => $entity->getId(),
                    'error' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * Load multiple entities by IDs
     */
    public function loadMultiple(EntityType $entityType, array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        // Load entity records
        $records = $this->db->table($entityType->getEntityTable())
            ->whereIn('entity_id', $ids)
            ->get();

        $recordMap = [];
        foreach ($records as $record) {
            $recordMap[(int)$record['entity_id']] = $record;
        }

        // Load all values for these entities
        $allValues = $this->valueManager->loadMultiple($ids, $entityType->getAttributes());

        // Build entity instances
        $entities = [];
        foreach ($ids as $id) {
            if (!isset($recordMap[$id])) {
                continue; // Entity not found
            }

            $entity = new Entity($entityType);
            $entity->setId($id);

            $record = $recordMap[$id];
            if (isset($record['created_at'])) {
                $entity->setCreatedAt($record['created_at']);
            }
            if (isset($record['updated_at'])) {
                $entity->setUpdatedAt($record['updated_at']);
            }

            // Set values
            if (isset($allValues[$id])) {
                $entity->setDataValues($allValues[$id]);
            }

            $entity->clearDirtyTracking();
            $entities[$id] = $entity;
        }

        return $entities;
    }

    /**
     * Find entities by criteria
     */
    public function findBy(EntityType $entityType, array $criteria, int $limit = null): array
    {
        // Build query
        $query = $this->db->table($entityType->getEntityTable());

        if ($limit) {
            $query->limit($limit);
        }

        $records = $query->get();

        if (empty($records)) {
            return [];
        }

        $ids = array_column($records, 'entity_id');
        return $this->loadMultiple($entityType, $ids);
    }
}
