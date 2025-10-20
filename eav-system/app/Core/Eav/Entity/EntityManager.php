<?php
// app/Core/Eav/Entity/EntityManager.php
namespace Core\Eav\Entity;

use Core\Eav\Cache\RequestCache;
use Core\Eav\Cache\IdentityMap;
use Core\Eav\Storage\StorageStrategy;
use Core\Di\Injectable;
use Core\Events\EventAware;

/**
 * Entity Manager
 * 
 * Central component for managing EAV entities with integrated caching
 */
class EntityManager
{
    use Injectable, EventAware;

    private StorageStrategy $storage;
    private RequestCache $requestCache;
    private IdentityMap $identityMap;
    private array $config;

    public function __construct(StorageStrategy $storage)
    {
        $this->storage = $storage;
        $this->requestCache = new RequestCache();
        $this->identityMap = new IdentityMap();
        $this->config = require APP_PATH . 'Core/Eav/config.php';
    }

    /**
     * Create new entity
     * 
     * @param string $entityType
     * @return Entity
     */
    public function create(string $entityType): Entity
    {
        $entity = new Entity($entityType);
        
        // Fire creation event
        $this->fireEvent('entity.create', [
            'entity_type' => $entityType,
            'entity' => $entity,
        ]);

        return $entity;
    }

    /**
     * Load entity by ID with multi-level cache
     * 
     * @param string $entityType
     * @param int $id
     * @return Entity|null
     */
    public function load(string $entityType, int $id): ?Entity
    {
        // L1 Cache: Check identity map first
        $entity = $this->identityMap->get($entityType, $id);
        if ($entity) {
            return $entity;
        }

        // L1 Cache: Check request cache
        $cacheKey = $this->makeCacheKey($entityType, $id);
        $cachedData = $this->requestCache->get($cacheKey);
        
        if ($cachedData) {
            $entity = new Entity($entityType);
            $entity->fromArray($cachedData);
            $this->identityMap->set($entity);
            return $entity;
        }

        // Fire before load event
        $this->fireEvent('entity.before_load', [
            'entity_type' => $entityType,
            'id' => $id,
        ]);

        // Load from storage
        $entity = $this->storage->load($entityType, $id);

        if ($entity) {
            // Cache in L1
            $this->requestCache->set($cacheKey, $entity->toArray());
            $this->identityMap->set($entity);

            // Fire after load event
            $this->fireEvent('entity.after_load', [
                'entity' => $entity,
            ]);
        }

        return $entity;
    }

    /**
     * Load multiple entities
     * 
     * @param string $entityType
     * @param array $ids
     * @return array
     */
    public function loadMultiple(string $entityType, array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $entities = [];
        $idsToLoad = [];

        // Check L1 caches first
        foreach ($ids as $id) {
            $entity = $this->identityMap->get($entityType, $id);
            if ($entity) {
                $entities[$id] = $entity;
            } else {
                $idsToLoad[] = $id;
            }
        }

        // Load remaining from storage
        if (!empty($idsToLoad)) {
            $loadedEntities = $this->storage->loadMultiple($entityType, $idsToLoad);
            
            foreach ($loadedEntities as $entity) {
                $id = $entity->getId();
                $entities[$id] = $entity;
                
                // Cache in L1
                $cacheKey = $this->makeCacheKey($entityType, $id);
                $this->requestCache->set($cacheKey, $entity->toArray());
                $this->identityMap->set($entity);
            }
        }

        return array_values($entities);
    }

    /**
     * Save entity
     * 
     * @param Entity $entity
     * @return bool
     */
    public function save(Entity $entity): bool
    {
        // Fire before save event
        $this->fireEvent('entity.before_save', [
            'entity' => $entity,
            'is_new' => $entity->isNew(),
        ]);

        // Save to storage
        $result = $this->storage->save($entity);

        if ($result) {
            // Update L1 caches
            $cacheKey = $this->makeCacheKey($entity->getEntityType(), $entity->getId());
            $this->requestCache->set($cacheKey, $entity->toArray());
            $this->identityMap->set($entity);

            // Fire after save event
            $this->fireEvent('entity.after_save', [
                'entity' => $entity,
            ]);
        }

        return $result;
    }

    /**
     * Delete entity
     * 
     * @param Entity $entity
     * @return bool
     */
    public function delete(Entity $entity): bool
    {
        if ($entity->isNew()) {
            return false;
        }

        // Fire before delete event
        $this->fireEvent('entity.before_delete', [
            'entity' => $entity,
        ]);

        // Delete from storage
        $result = $this->storage->delete($entity);

        if ($result) {
            // Clear from L1 caches
            $cacheKey = $this->makeCacheKey($entity->getEntityType(), $entity->getId());
            $this->requestCache->delete($cacheKey);
            $this->identityMap->remove($entity->getEntityType(), $entity->getId());

            // Fire after delete event
            $this->fireEvent('entity.after_delete', [
                'entity' => $entity,
            ]);
        }

        return $result;
    }

    /**
     * Find entities by criteria
     * 
     * @param string $entityType
     * @param array $filters
     * @param array $sorts
     * @param int|null $limit
     * @param int|null $offset
     * @return array
     */
    public function find(
        string $entityType,
        array $filters = [],
        array $sorts = [],
        ?int $limit = null,
        ?int $offset = null
    ): array {
        return $this->storage->query($entityType, $filters, $sorts, $limit, $offset);
    }

    /**
     * Count entities
     * 
     * @param string $entityType
     * @param array $filters
     * @return int
     */
    public function count(string $entityType, array $filters = []): int
    {
        return $this->storage->count($entityType, $filters);
    }

    /**
     * Get request cache instance
     * 
     * @return RequestCache
     */
    public function getRequestCache(): RequestCache
    {
        return $this->requestCache;
    }

    /**
     * Get identity map instance
     * 
     * @return IdentityMap
     */
    public function getIdentityMap(): IdentityMap
    {
        return $this->identityMap;
    }

    /**
     * Clear L1 caches
     */
    public function clearCache(): void
    {
        $this->requestCache->clear();
        $this->identityMap->clear();
    }

    /**
     * Clear L1 caches for specific entity type
     * 
     * @param string $entityType
     */
    public function clearCacheForType(string $entityType): void
    {
        $this->requestCache->clearByPrefix($entityType . ':');
        $this->identityMap->clearType($entityType);
    }

    /**
     * Get cache statistics
     * 
     * @return array
     */
    public function getCacheStats(): array
    {
        return [
            'request_cache' => $this->requestCache->getStats(),
            'identity_map' => $this->identityMap->getStats(),
        ];
    }

    /**
     * Make cache key
     * 
     * @param string $entityType
     * @param int $id
     * @return string
     */
    private function makeCacheKey(string $entityType, int $id): string
    {
        return "{$entityType}:{$id}";
    }

    /**
     * Fire event
     * 
     * @param string $eventName
     * @param array $data
     */
    private function fireEvent(string $eventName, array $data = []): void
    {
        $eventsManager = $this->getDI()->get('eventsManager');
        if ($eventsManager) {
            $eventsManager->trigger($eventName, $data);
        }
    }
}
