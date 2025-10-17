<?php
// app/Core/Eav/Storage/StorageStrategyInterface.php
namespace Core\Eav\Storage;

/**
 * Interface for storage strategy implementations
 */
interface StorageStrategyInterface
{
    /**
     * Load attribute values for an entity
     *
     * @param int $entityId Entity ID
     * @param array $attributes Array of Attribute objects
     * @return array Associative array of attribute_code => value
     */
    public function loadValues(int $entityId, array $attributes): array;

    /**
     * Save attribute values for an entity
     *
     * @param int $entityId Entity ID
     * @param int $entityTypeId Entity type ID
     * @param array $values Associative array of attribute objects with values
     * @return bool Success status
     */
    public function saveValues(int $entityId, int $entityTypeId, array $values): bool;

    /**
     * Delete specific attribute values
     *
     * @param int $entityId Entity ID
     * @param array $attributeCodes Array of attribute codes to delete
     * @return bool Success status
     */
    public function deleteValues(int $entityId, array $attributeCodes): bool;

    /**
     * Delete all attribute values for an entity
     *
     * @param int $entityId Entity ID
     * @return bool Success status
     */
    public function deleteAllValues(int $entityId): bool;

    /**
     * Load values for multiple entities
     *
     * @param array $entityIds Array of entity IDs
     * @param array $attributes Array of Attribute objects
     * @return array Multi-dimensional array: entityId => [attribute_code => value]
     */
    public function loadMultiple(array $entityIds, array $attributes): array;

    /**
     * Get table name for backend type
     *
     * @param string $backendType Backend type (varchar, int, decimal, datetime, text)
     * @return string Table name
     */
    public function getValueTable(string $backendType): string;
}
