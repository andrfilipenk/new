<?php
// app/Core/Domain/RepositoryInterface.php
namespace Core\Domain;

/**
 * Repository Interface for Domain Layer
 * 
 * Provides abstraction for data access without exposing
 * implementation details to the domain
 */
interface RepositoryInterface
{
    /**
     * Find entity by ID
     *
     * @param mixed $id Entity identifier
     * @return object|null Entity or null if not found
     */
    public function findById($id): ?object;

    /**
     * Find entities by criteria
     *
     * @param array $criteria Search criteria
     * @return array Array of entities
     */
    public function findBy(array $criteria): array;

    /**
     * Find one entity by criteria
     *
     * @param array $criteria Search criteria
     * @return object|null Entity or null if not found
     */
    public function findOneBy(array $criteria): ?object;

    /**
     * Get all entities
     *
     * @return array Array of all entities
     */
    public function findAll(): array;

    /**
     * Save entity
     *
     * @param object $entity Entity to save
     * @return object Saved entity
     */
    public function save(object $entity): object;

    /**
     * Delete entity
     *
     * @param object $entity Entity to delete
     * @return bool Success status
     */
    public function delete(object $entity): bool;

    /**
     * Count entities by criteria
     *
     * @param array $criteria Search criteria
     * @return int Number of entities
     */
    public function count(array $criteria = []): int;

    /**
     * Check if entity exists
     *
     * @param mixed $id Entity identifier
     * @return bool True if exists
     */
    public function exists($id): bool;
}