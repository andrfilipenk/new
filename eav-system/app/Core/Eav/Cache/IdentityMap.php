<?php
// app/Core/Eav/Cache/IdentityMap.php
namespace Core\Eav\Cache;

use Core\Eav\Entity\Entity;

/**
 * Identity Map Pattern for Entity Instances
 * 
 * Ensures only one instance of each entity exists per request
 * Part of L1 cache layer
 */
class IdentityMap
{
    private array $entities = [];

    /**
     * Get entity from identity map
     * 
     * @param string $entityType
     * @param int $id
     * @return Entity|null
     */
    public function get(string $entityType, int $id): ?Entity
    {
        $key = $this->makeKey($entityType, $id);
        return $this->entities[$key] ?? null;
    }

    /**
     * Add entity to identity map
     */
    public function set(Entity $entity): void
    {
        if ($entity->getId() === null) {
            return; // Don't cache unsaved entities
        }

        $key = $this->makeKey($entity->getEntityType(), $entity->getId());
        $this->entities[$key] = $entity;
    }

    /**
     * Check if entity exists in map
     */
    public function has(string $entityType, int $id): bool
    {
        $key = $this->makeKey($entityType, $id);
        return isset($this->entities[$key]);
    }

    /**
     * Remove entity from map
     */
    public function remove(string $entityType, int $id): void
    {
        $key = $this->makeKey($entityType, $id);
        unset($this->entities[$key]);
    }

    /**
     * Clear all entities of a type
     */
    public function clearType(string $entityType): void
    {
        foreach (array_keys($this->entities) as $key) {
            if (str_starts_with($key, $entityType . ':')) {
                unset($this->entities[$key]);
            }
        }
    }

    /**
     * Clear all entities
     */
    public function clear(): void
    {
        $this->entities = [];
    }

    /**
     * Get all entities of a type
     */
    public function getByType(string $entityType): array
    {
        $result = [];
        foreach ($this->entities as $key => $entity) {
            if (str_starts_with($key, $entityType . ':')) {
                $result[] = $entity;
            }
        }
        return $result;
    }

    /**
     * Get statistics
     */
    public function getStats(): array
    {
        $byType = [];
        foreach ($this->entities as $entity) {
            $type = $entity->getEntityType();
            $byType[$type] = ($byType[$type] ?? 0) + 1;
        }

        return [
            'total' => count($this->entities),
            'by_type' => $byType,
        ];
    }

    /**
     * Make cache key for entity
     */
    private function makeKey(string $entityType, int $id): string
    {
        return "{$entityType}:{$id}";
    }
}
