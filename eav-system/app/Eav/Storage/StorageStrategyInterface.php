<?php
// app/Eav/Storage/StorageStrategyInterface.php
namespace Eav\Storage;

/**
 * Storage Strategy Interface
 * 
 * Defines contract for type-specific value storage handlers
 */
interface StorageStrategyInterface
{
    /**
     * Get the backend type this strategy handles
     */
    public function getBackendType(): string;

    /**
     * Get the table name for this storage type
     */
    public function getTableName(): string;

    /**
     * Save a value
     */
    public function saveValue(int $entityId, int $attributeId, mixed $value): bool;

    /**
     * Get a value
     */
    public function getValue(int $entityId, int $attributeId): mixed;

    /**
     * Delete a value
     */
    public function deleteValue(int $entityId, int $attributeId): bool;

    /**
     * Get multiple values for an entity
     */
    public function getEntityValues(int $entityId, array $attributeIds = []): array;

    /**
     * Save multiple values for an entity
     */
    public function saveEntityValues(int $entityId, array $values): bool;

    /**
     * Validate value before storage
     */
    public function validateValue(mixed $value): bool;

    /**
     * Transform value for storage
     */
    public function transformForStorage(mixed $value): mixed;

    /**
     * Transform value from storage
     */
    public function transformFromStorage(mixed $value): mixed;
}
