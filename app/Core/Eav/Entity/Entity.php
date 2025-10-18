<?php
// app/Core/Eav/Entity/Entity.php
namespace Core\Eav\Entity;

use Core\Di\Injectable;
use Core\Events\EventAware;

/**
 * Base EAV Entity Class
 * 
 * Represents an entity in the EAV system with dirty tracking for performance optimization
 */
class Entity
{
    use Injectable, EventAware;

    protected ?int $id = null;
    protected string $entityType;
    protected array $attributes = [];
    protected array $originalAttributes = [];
    protected array $dirtyAttributes = [];
    protected ?string $createdAt = null;
    protected ?string $updatedAt = null;
    protected bool $isNew = true;

    public function __construct(string $entityType, ?int $id = null)
    {
        $this->entityType = $entityType;
        if ($id !== null) {
            $this->id = $id;
            $this->isNew = false;
        }
    }

    /**
     * Get entity ID
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set entity ID
     */
    public function setId(int $id): self
    {
        $this->id = $id;
        $this->isNew = false;
        return $this;
    }

    /**
     * Get entity type code
     */
    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * Check if entity is new (not persisted)
     */
    public function isNew(): bool
    {
        return $this->isNew;
    }

    /**
     * Set attribute value with dirty tracking
     */
    public function setAttribute(string $code, mixed $value): self
    {
        // Track original value on first set
        if (!isset($this->originalAttributes[$code]) && isset($this->attributes[$code])) {
            $this->originalAttributes[$code] = $this->attributes[$code];
        }

        // Set new value
        $this->attributes[$code] = $value;

        // Mark as dirty if value changed
        if (!isset($this->originalAttributes[$code]) || $this->originalAttributes[$code] !== $value) {
            $this->dirtyAttributes[$code] = true;
        }

        return $this;
    }

    /**
     * Get attribute value
     */
    public function getAttribute(string $code, mixed $default = null): mixed
    {
        return $this->attributes[$code] ?? $default;
    }

    /**
     * Check if attribute exists
     */
    public function hasAttribute(string $code): bool
    {
        return isset($this->attributes[$code]);
    }

    /**
     * Get all attributes
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Set multiple attributes
     */
    public function setAttributes(array $attributes): self
    {
        foreach ($attributes as $code => $value) {
            $this->setAttribute($code, $value);
        }
        return $this;
    }

    /**
     * Get dirty (modified) attributes
     */
    public function getDirtyAttributes(): array
    {
        $dirty = [];
        foreach (array_keys($this->dirtyAttributes) as $code) {
            if (isset($this->attributes[$code])) {
                $dirty[$code] = $this->attributes[$code];
            }
        }
        return $dirty;
    }

    /**
     * Check if entity has dirty attributes
     */
    public function isDirty(): bool
    {
        return !empty($this->dirtyAttributes);
    }

    /**
     * Check if specific attribute is dirty
     */
    public function isAttributeDirty(string $code): bool
    {
        return isset($this->dirtyAttributes[$code]);
    }

    /**
     * Reset dirty tracking (called after save)
     */
    public function resetDirtyTracking(): void
    {
        $this->originalAttributes = $this->attributes;
        $this->dirtyAttributes = [];
    }

    /**
     * Get original attribute value (before modification)
     */
    public function getOriginalAttribute(string $code): mixed
    {
        return $this->originalAttributes[$code] ?? $this->attributes[$code] ?? null;
    }

    /**
     * Get created timestamp
     */
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    /**
     * Set created timestamp
     */
    public function setCreatedAt(string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Get updated timestamp
     */
    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    /**
     * Set updated timestamp
     */
    public function setUpdatedAt(string $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Magic getter for attributes
     */
    public function __get(string $name): mixed
    {
        return $this->getAttribute($name);
    }

    /**
     * Magic setter for attributes
     */
    public function __set(string $name, mixed $value): void
    {
        $this->setAttribute($name, $value);
    }

    /**
     * Magic isset for attributes
     */
    public function __isset(string $name): bool
    {
        return $this->hasAttribute($name);
    }

    /**
     * Convert entity to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'entity_type' => $this->entityType,
            'attributes' => $this->attributes,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    /**
     * Load entity data from array
     */
    public function fromArray(array $data): self
    {
        if (isset($data['id'])) {
            $this->setId($data['id']);
        }
        if (isset($data['created_at'])) {
            $this->setCreatedAt($data['created_at']);
        }
        if (isset($data['updated_at'])) {
            $this->setUpdatedAt($data['updated_at']);
        }
        if (isset($data['attributes'])) {
            foreach ($data['attributes'] as $code => $value) {
                $this->attributes[$code] = $value;
            }
            // Reset dirty tracking after loading
            $this->originalAttributes = $this->attributes;
            $this->dirtyAttributes = [];
        }
        return $this;
    }
}
