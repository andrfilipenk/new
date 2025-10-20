<?php
// app/Eav/Services/EntityManager.php
namespace Eav\Services;

use Core\Database\Database;
use Eav\Models\Entity;
use Eav\Models\EntityType;
use Eav\Repositories\ValueRepository;
use Eav\Repositories\AttributeRepository;
use Eav\Cache\CacheManager;
use Core\Events\Manager as EventManager;

/**
 * Entity Manager
 * 
 * Core entity lifecycle management (create, update, delete, find) with event dispatching
 */
class EntityManager
{
    private Database $db;
    private ValueRepository $valueRepository;
    private AttributeRepository $attributeRepository;
    private CacheManager $cache;
    private ?EventManager $eventManager;

    public function __construct(
        Database $db,
        ValueRepository $valueRepository,
        AttributeRepository $attributeRepository,
        CacheManager $cache,
        ?EventManager $eventManager = null
    ) {
        $this->db = $db;
        $this->valueRepository = $valueRepository;
        $this->attributeRepository = $attributeRepository;
        $this->cache = $cache;
        $this->eventManager = $eventManager;
    }

    /**
     * Create a new entity
     */
    public function create(int $entityTypeId, array $data, ?int $parentId = null): Entity
    {
        $this->fireEvent('eav:entity:creating', ['entity_type_id' => $entityTypeId, 'data' => $data]);

        // Start transaction
        $this->db->beginTransaction();

        try {
            // Create entity record
            $entity = new Entity([
                'entity_type_id' => $entityTypeId,
                'parent_id' => $parentId,
                'entity_code' => $data['entity_code'] ?? null,
                'is_active' => $data['is_active'] ?? true
            ]);

            if (!$entity->save()) {
                throw new \Exception('Failed to create entity');
            }

            // Get attributes for this entity type
            $attributes = $this->attributeRepository->getByEntityType($entityTypeId);

            // Validate and prepare attribute values
            $attributeValues = $this->prepareAttributeValues($data, $attributes);

            // Save attribute values
            if (!empty($attributeValues)) {
                $this->valueRepository->saveEntityValues($entity->id, $attributeValues, $attributes);
            }

            $this->db->commit();

            // Clear cache
            $this->cache->invalidateEntityType($entityTypeId);

            $this->fireEvent('eav:entity:created', ['entity' => $entity]);

            return $entity;

        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Find entity by ID with all attribute values
     */
    public function find(int $entityId, bool $loadValues = true): ?Entity
    {
        $cacheKey = "entity:{$entityId}";

        if ($loadValues) {
            $cacheKey .= ":full";
        }

        // Try cache first
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            $entity = Entity::newFromBuilder($cached['entity']);
            if ($loadValues && isset($cached['values'])) {
                $entity->attributeValues = $cached['values'];
            }
            return $entity;
        }

        // Load from database
        $entity = Entity::find($entityId);
        
        if (!$entity) {
            return null;
        }

        if ($loadValues) {
            $attributes = $this->attributeRepository->getByEntityType($entity->entity_type_id);
            $values = $this->valueRepository->getEntityValues($entityId, $attributes);
            $entity->attributeValues = $values;

            // Cache the full entity with values
            $this->cache->set($cacheKey, [
                'entity' => $entity->getData(),
                'values' => $values
            ], 1800);
        } else {
            // Cache just the entity
            $this->cache->set($cacheKey, [
                'entity' => $entity->getData()
            ], 1800);
        }

        return $entity;
    }

    /**
     * Find multiple entities by IDs
     */
    public function findMany(array $entityIds, bool $loadValues = true): array
    {
        if (empty($entityIds)) {
            return [];
        }

        $entities = Entity::findMany($entityIds);

        if ($loadValues && !empty($entities)) {
            // Group entities by type for efficient loading
            $entitiesByType = [];
            foreach ($entities as $entity) {
                $typeId = $entity->entity_type_id;
                if (!isset($entitiesByType[$typeId])) {
                    $entitiesByType[$typeId] = [];
                }
                $entitiesByType[$typeId][] = $entity;
            }

            // Load values for each type
            foreach ($entitiesByType as $typeId => $typeEntities) {
                $attributes = $this->attributeRepository->getByEntityType($typeId);
                $entityIds = array_map(fn($e) => $e->id, $typeEntities);
                
                $allValues = $this->valueRepository->getMultipleEntityValues($entityIds, $attributes);

                // Assign values to entities
                foreach ($typeEntities as $entity) {
                    $entity->attributeValues = $allValues[$entity->id] ?? [];
                }
            }
        }

        return $entities;
    }

    /**
     * Update an entity
     */
    public function update(int $entityId, array $data): bool
    {
        $entity = Entity::find($entityId);
        
        if (!$entity) {
            return false;
        }

        $this->fireEvent('eav:entity:updating', ['entity' => $entity, 'data' => $data]);

        $this->db->beginTransaction();

        try {
            // Update entity record if needed
            if (isset($data['entity_code'])) {
                $entity->entity_code = $data['entity_code'];
            }
            if (isset($data['is_active'])) {
                $entity->is_active = $data['is_active'];
            }

            $entity->save();

            // Get attributes
            $attributes = $this->attributeRepository->getByEntityType($entity->entity_type_id);

            // Prepare and save attribute values
            $attributeValues = $this->prepareAttributeValues($data, $attributes);
            
            if (!empty($attributeValues)) {
                $this->valueRepository->saveEntityValues($entityId, $attributeValues, $attributes);
            }

            $this->db->commit();

            // Invalidate cache
            $this->cache->invalidateEntity($entityId);
            $this->cache->invalidateEntityType($entity->entity_type_id);

            $this->fireEvent('eav:entity:updated', ['entity' => $entity]);

            return true;

        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Delete an entity
     */
    public function delete(int $entityId, bool $soft = true): bool
    {
        $entity = Entity::find($entityId);
        
        if (!$entity) {
            return false;
        }

        $this->fireEvent('eav:entity:deleting', ['entity' => $entity]);

        $this->db->beginTransaction();

        try {
            if ($soft) {
                // Soft delete
                $result = $entity->delete();
            } else {
                // Hard delete - remove all values first
                $this->valueRepository->deleteEntityValues($entityId);
                $result = $entity->forceDelete();
            }

            $this->db->commit();

            // Invalidate cache
            $this->cache->invalidateEntity($entityId);
            $this->cache->invalidateEntityType($entity->entity_type_id);

            $this->fireEvent('eav:entity:deleted', ['entity' => $entity]);

            return $result;

        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Get attribute value for an entity
     */
    public function getAttributeValue(int $entityId, string $attributeCode): mixed
    {
        $entity = Entity::find($entityId);
        if (!$entity) {
            return null;
        }

        $attribute = $this->attributeRepository->findByCode($attributeCode, $entity->entity_type_id);
        if (!$attribute) {
            return null;
        }

        return $this->valueRepository->getValue($entityId, $attribute);
    }

    /**
     * Set attribute value for an entity
     */
    public function setAttributeValue(int $entityId, string $attributeCode, mixed $value): bool
    {
        $entity = Entity::find($entityId);
        if (!$entity) {
            return false;
        }

        $attribute = $this->attributeRepository->findByCode($attributeCode, $entity->entity_type_id);
        if (!$attribute) {
            return false;
        }

        // Validate value
        if (!$this->attributeRepository->validateValue($attribute, $value)) {
            return false;
        }

        $result = $this->valueRepository->saveValue($entityId, $attribute, $value);

        if ($result) {
            $this->cache->invalidateEntity($entityId);
        }

        return $result;
    }

    /**
     * Copy an entity
     */
    public function copy(int $sourceEntityId, ?array $overrideData = null): ?Entity
    {
        $source = $this->find($sourceEntityId, true);
        if (!$source) {
            return null;
        }

        $data = $source->attributeValues ?? [];
        
        if ($overrideData) {
            $data = array_merge($data, $overrideData);
        }

        return $this->create($source->entity_type_id, $data, $source->parent_id);
    }

    /**
     * Prepare attribute values from input data
     */
    private function prepareAttributeValues(array $data, array $attributes): array
    {
        $values = [];

        foreach ($attributes as $attribute) {
            $code = $attribute->attribute_code;

            // Skip if not in data
            if (!isset($data[$code])) {
                continue;
            }

            $value = $data[$code];

            // Validate value
            if (!$this->attributeRepository->validateValue($attribute, $value)) {
                throw new \InvalidArgumentException(
                    "Invalid value for attribute '{$code}'"
                );
            }

            $values[$code] = $value;
        }

        return $values;
    }

    /**
     * Fire an event
     */
    private function fireEvent(string $event, array $data = []): void
    {
        if ($this->eventManager) {
            $this->eventManager->trigger($event, $this, $data);
        }
    }

    /**
     * Check if entity exists
     */
    public function exists(int $entityId): bool
    {
        return Entity::find($entityId) !== null;
    }

    /**
     * Get entity count for entity type
     */
    public function countByType(int $entityTypeId): int
    {
        return Entity::where('entity_type_id', $entityTypeId)
            ->where('is_active', true)
            ->count();
    }
}
