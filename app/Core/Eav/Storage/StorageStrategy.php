<?php
// app/Core/Eav/Storage/StorageStrategy.php
namespace Core\Eav\Storage;

use Core\Eav\Entity\Entity;

/**
 * Storage Strategy Interface
 * 
 * Defines the contract for entity storage implementations
 */
interface StorageStrategy
{
    /**
     * Load entity by ID
     * 
     * @param string $entityType
     * @param int $id
     * @return Entity|null
     */
    public function load(string $entityType, int $id): ?Entity;

    /**
     * Load multiple entities by IDs
     * 
     * @param string $entityType
     * @param array $ids
     * @return array
     */
    public function loadMultiple(string $entityType, array $ids): array;

    /**
     * Save entity
     * 
     * @param Entity $entity
     * @return bool
     */
    public function save(Entity $entity): bool;

    /**
     * Delete entity
     * 
     * @param Entity $entity
     * @return bool
     */
    public function delete(Entity $entity): bool;

    /**
     * Execute query
     * 
     * @param string $entityType
     * @param array $filters
     * @param array $sorts
     * @param int|null $limit
     * @param int|null $offset
     * @return array
     */
    public function query(
        string $entityType,
        array $filters = [],
        array $sorts = [],
        ?int $limit = null,
        ?int $offset = null
    ): array;

    /**
     * Count entities matching criteria
     * 
     * @param string $entityType
     * @param array $filters
     * @return int
     */
    public function count(string $entityType, array $filters = []): int;

    /**
     * Check if storage is available
     * 
     * @return bool
     */
    public function isAvailable(): bool;
}
