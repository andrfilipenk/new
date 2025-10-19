<?php
// app/Core/Eav/Model/Entity.php
namespace Core\Eav\Model;

use Core\Exception\ValidationException;

/**
 * Represents an entity instance with attributes and dirty tracking
 */
class Entity
{
    private EntityType $entityType;
    private ?int $entityId = null;
    private array $data = [];
    private array $originalData = [];
    private array $dirtyAttributes = [];
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    public function __construct(EntityType $entityType)
    {
        $this->entityType = $entityType;
    }

    public function getEntityType(): EntityType
    {
        return $this->entityType;
    }

    public function getId(): ?int
    {
        return $this->entityId;
    }

    public function setId(int $id): void
    {
        $this->entityId = $id;
    }

    /**
     * Set attribute value
     */
    public function set(string $attributeCode, mixed $value): void
    {
        if (!$this->entityType->hasAttribute($attributeCode)) {
            throw new ValidationException(
                [$attributeCode => "Attribute '{$attributeCode}' does not exist for entity type '{$this->entityType->getCode()}'"],
                "Invalid attribute"
            );
        }

        $this->data[$attributeCode] = $value;
        
        // Track dirty state
        if (!isset($this->originalData[$attributeCode]) || $this->originalData[$attributeCode] !== $value) {
            $this->dirtyAttributes[$attributeCode] = true;
        }
    }

    /**
     * Get attribute value
     */
    public function get(string $attributeCode): mixed
    {
        return $this->data[$attributeCode] ?? null;
    }

    /**
     * Set multiple attribute values
     */
    public function setDataValues(array $data): void
    {
        foreach ($data as $code => $value) {
            $this->set($code, $value);
        }
    }

    /**
     * Get all data
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get dirty (modified) attributes
     */
    public function getDirtyAttributes(): array
    {
        return array_keys($this->dirtyAttributes);
    }

    /**
     * Check if entity has changes
     */
    public function isDirty(): bool
    {
        return !empty($this->dirtyAttributes);
    }

    /**
     * Get only dirty attribute values
     */
    public function getDirtyData(): array
    {
        $dirty = [];
        foreach ($this->dirtyAttributes as $code => $_) {
            $dirty[$code] = $this->data[$code] ?? null;
        }
        return $dirty;
    }

    /**
     * Clear dirty tracking (after save)
     */
    public function clearDirtyTracking(): void
    {
        $this->dirtyAttributes = [];
        $this->originalData = $this->data;
    }

    /**
     * Validate entity data
     */
    public function validate(): array
    {
        $errors = [];
        
        foreach ($this->entityType->getAttributes() as $attribute) {
            $code = $attribute->getCode();
            $value = $this->get($code);

            // Required validation
            if ($attribute->isRequired() && ($value === null || $value === '')) {
                $errors[$code] = "Attribute '{$attribute->getLabel()}' is required";
                continue;
            }

            // Type validation based on backend type
            if ($value !== null) {
                $typeError = $this->validateType($attribute, $value);
                if ($typeError) {
                    $errors[$code] = $typeError;
                }
            }
        }

        return $errors;
    }

    private function validateType(Attribute $attribute, mixed $value): ?string
    {
        $backendType = $attribute->getBackendType();
        
        switch ($backendType) {
            case 'int':
                if (!is_numeric($value) || (string)(int)$value !== (string)$value) {
                    return "Attribute '{$attribute->getLabel()}' must be an integer";
                }
                break;
            case 'decimal':
                if (!is_numeric($value)) {
                    return "Attribute '{$attribute->getLabel()}' must be a number";
                }
                break;
            case 'datetime':
                if (!strtotime($value)) {
                    return "Attribute '{$attribute->getLabel()}' must be a valid date/time";
                }
                break;
            case 'varchar':
            case 'text':
                if (!is_string($value) && !is_numeric($value)) {
                    return "Attribute '{$attribute->getLabel()}' must be a string";
                }
                break;
        }
        
        return null;
    }

    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }
    public function setCreatedAt(string $createdAt): void { $this->createdAt = $createdAt; }
    public function setUpdatedAt(string $updatedAt): void { $this->updatedAt = $updatedAt; }
}
