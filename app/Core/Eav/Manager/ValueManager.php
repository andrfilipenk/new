<?php
// app/Core/Eav/Manager/ValueManager.php
namespace Core\Eav\Manager;

use Core\Eav\Model\EntityType;
use Core\Eav\Model\AttributeCollection;
use Core\Eav\Model\Attribute;
use Core\Eav\Storage\StorageStrategyInterface;
use Core\Eav\Exception\StorageException;
use Core\Di\Injectable;

/**
 * Coordinates attribute value persistence
 */
class ValueManager
{
    use Injectable;

    private StorageStrategyInterface $storage;
    private AttributeMetadataManager $attributeMetadata;

    public function __construct(
        StorageStrategyInterface $storage,
        AttributeMetadataManager $attributeMetadata
    ) {
        $this->storage = $storage;
        $this->attributeMetadata = $attributeMetadata;
    }

    /**
     * Load all attribute values for an entity
     */
    public function loadValues(int $entityId, AttributeCollection $attributes): array
    {
        if ($attributes->count() === 0) {
            return [];
        }

        // Ensure all attributes have IDs
        $attributeArray = [];
        foreach ($attributes as $attribute) {
            if ($attribute->getAttributeId() === null) {
                throw new StorageException(
                    "Attribute '{$attribute->getCode()}' does not have an ID",
                    "Attribute metadata missing",
                    ['attribute_code' => $attribute->getCode()]
                );
            }
            $attributeArray[] = $attribute;
        }

        return $this->storage->loadValues($entityId, $attributeArray);
    }

    /**
     * Save attribute values for an entity
     */
    public function saveValues(int $entityId, EntityType $entityType, array $values): bool
    {
        if (empty($values)) {
            return true;
        }

        $entityTypeId = $entityType->getEntityTypeId();
        if (!$entityTypeId) {
            throw new StorageException(
                "Entity type must have an ID before saving values",
                "Invalid entity type",
                ['entity_type' => $entityType->getCode()]
            );
        }

        // Map attribute codes to attribute objects
        $attributeValues = [];
        foreach ($values as $code => $value) {
            $attribute = $entityType->getAttribute($code);
            if (!$attribute) {
                continue; // Skip unknown attributes
            }

            if ($attribute->getAttributeId() === null) {
                throw new StorageException(
                    "Attribute '{$code}' does not have an ID",
                    "Attribute metadata missing",
                    ['attribute_code' => $code]
                );
            }

            $attributeValues[$attribute] = $value;
        }

        return $this->storage->saveValues($entityId, $entityTypeId, $attributeValues);
    }

    /**
     * Delete specific attribute values
     */
    public function deleteValues(int $entityId, array $attributeCodes): bool
    {
        return $this->storage->deleteValues($entityId, $attributeCodes);
    }

    /**
     * Load single attribute value
     */
    public function loadValue(int $entityId, Attribute $attribute): mixed
    {
        if ($attribute->getAttributeId() === null) {
            throw new StorageException(
                "Attribute '{$attribute->getCode()}' does not have an ID",
                "Attribute metadata missing",
                ['attribute_code' => $attribute->getCode()]
            );
        }

        $values = $this->storage->loadValues($entityId, [$attribute]);
        return $values[$attribute->getCode()] ?? null;
    }

    /**
     * Save single attribute value
     */
    public function saveValue(int $entityId, int $entityTypeId, Attribute $attribute, mixed $value): bool
    {
        if ($attribute->getAttributeId() === null) {
            throw new StorageException(
                "Attribute '{$attribute->getCode()}' does not have an ID",
                "Attribute metadata missing",
                ['attribute_code' => $attribute->getCode()]
            );
        }

        return $this->storage->saveValues($entityId, $entityTypeId, [$attribute => $value]);
    }

    /**
     * Load values for multiple entities
     */
    public function loadMultiple(array $entityIds, AttributeCollection $attributes): array
    {
        if (empty($entityIds) || $attributes->count() === 0) {
            return [];
        }

        // Ensure all attributes have IDs
        $attributeArray = [];
        foreach ($attributes as $attribute) {
            if ($attribute->getAttributeId() === null) {
                throw new StorageException(
                    "Attribute '{$attribute->getCode()}' does not have an ID",
                    "Attribute metadata missing",
                    ['attribute_code' => $attribute->getCode()]
                );
            }
            $attributeArray[] = $attribute;
        }

        return $this->storage->loadMultiple($entityIds, $attributeArray);
    }
}
