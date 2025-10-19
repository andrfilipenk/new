<?php
// app/Eav/Model/Entity.php
namespace Eav\Model;

use Eav\Exception\ValidationException;

/**
 * EAV Entity Model
 * 
 * Represents a single entity instance with its attributes.
 */
class Entity
{
    /**
     * Entity ID
     */
    protected ?int $entityId = null;

    /**
     * Entity type
     */
    protected EntityType $entityType;

    /**
     * Attribute values
     */
    protected array $data = [];

    /**
     * Original data (for change tracking)
     */
    protected array $originalData = [];

    /**
     * Dirty attributes (changed since load)
     */
    protected array $dirtyAttributes = [];

    /**
     * Created at timestamp
     */
    protected ?string $createdAt = null;

    /**
     * Updated at timestamp
     */
    protected ?string $updatedAt = null;

    /**
     * Constructor
     */
    public function __construct(EntityType $entityType, array $data = [])
    {
        $this->entityType = $entityType;
        $this->setData($data);
        $this->originalData = $this->data;
    }

    /**
     * Get entity ID
     */
    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    /**
     * Set entity ID
     */
    public function setEntityId(int $id): self
    {
        $this->entityId = $id;
        return $this;
    }

    /**
     * Get entity type
     */
    public function getEntityType(): EntityType
    {
        return $this->entityType;
    }

    /**
     * Set multiple attribute values
     */
    public function setData(array $data): self
    {
        foreach ($data as $key => $value) {
            $this->setDataValue($key, $value);
        }
        return $this;
    }

    /**
     * Set a single attribute value
     */
    public function setDataValue(string $attributeCode, $value): self
    {
        // Special handling for entity_id, created_at, updated_at
        if ($attributeCode === 'entity_id') {
            $this->entityId = (int)$value;
            return $this;
        }
        if ($attributeCode === 'created_at') {
            $this->createdAt = $value;
            return $this;
        }
        if ($attributeCode === 'updated_at') {
            $this->updatedAt = $value;
            return $this;
        }

        // Get attribute definition
        $attribute = $this->entityType->getAttribute($attributeCode);
        if (!$attribute) {
            // Allow setting undefined attributes (they won't be persisted)
            $this->data[$attributeCode] = $value;
            return $this;
        }

        // Cast value to appropriate type
        $castedValue = $attribute->cast($value);
        
        // Mark as dirty if changed
        if (!isset($this->originalData[$attributeCode]) || 
            $this->originalData[$attributeCode] !== $castedValue) {
            $this->dirtyAttributes[$attributeCode] = true;
        }

        $this->data[$attributeCode] = $castedValue;
        return $this;
    }

    /**
     * Get an attribute value
     */
    public function getDataValue(string $attributeCode, $default = null)
    {
        return $this->data[$attributeCode] ?? $default;
    }

    /**
     * Get all data
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Check if attribute has been modified
     */
    public function isDirty($attributeCode = null): bool
    {
        if ($attributeCode === null) {
            return !empty($this->dirtyAttributes);
        }
        return isset($this->dirtyAttributes[$attributeCode]);
    }

    /**
     * Get dirty attributes
     */
    public function getDirtyAttributes(): array
    {
        return array_keys($this->dirtyAttributes);
    }

    /**
     * Get only dirty data (changed values)
     */
    public function getDirtyData(): array
    {
        $dirty = [];
        foreach ($this->dirtyAttributes as $code => $isDirty) {
            if ($isDirty && isset($this->data[$code])) {
                $dirty[$code] = $this->data[$code];
            }
        }
        return $dirty;
    }

    /**
     * Mark entity as clean (no changes)
     */
    public function markClean(): self
    {
        $this->originalData = $this->data;
        $this->dirtyAttributes = [];
        return $this;
    }

    /**
     * Validate all attributes
     */
    public function validate(): bool
    {
        $errors = [];

        // Validate each attribute
        foreach ($this->entityType->getAttributes() as $attribute) {
            $code = $attribute->getAttributeCode();
            $value = $this->getDataValue($code);

            try {
                $attribute->validate($value);
            } catch (ValidationException $e) {
                $errors = array_merge($errors, $e->getValidationErrors());
            }
        }

        if (!empty($errors)) {
            throw ValidationException::multipleErrors($errors);
        }

        return true;
    }

    /**
     * Get created at timestamp
     */
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    /**
     * Get updated at timestamp
     */
    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    /**
     * Magic getter for attributes
     */
    public function __get(string $name)
    {
        return $this->getDataValue($name);
    }

    /**
     * Magic setter for attributes
     */
    public function __set(string $name, $value)
    {
        $this->setDataValue($name, $value);
    }

    /**
     * Magic isset for attributes
     */
    public function __isset(string $name): bool
    {
        return isset($this->data[$name]);
    }

    /**
     * Convert entity to array
     */
    public function toArray(): array
    {
        return array_merge(
            [
                'entity_id' => $this->entityId,
                'created_at' => $this->createdAt,
                'updated_at' => $this->updatedAt,
            ],
            $this->data
        );
    }
}
