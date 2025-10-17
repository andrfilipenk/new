<?php
// app/Eav/Model/AttributeCollection.php
namespace Eav\Model;

use Eav\Exception\ConfigurationException;

/**
 * Attribute Collection
 * 
 * Manages a collection of attributes for an entity type.
 */
class AttributeCollection implements \Countable, \Iterator
{
    /**
     * Attributes indexed by code
     */
    protected array $attributes = [];

    /**
     * Current position for iterator
     */
    protected int $position = 0;

    /**
     * Array keys for iteration
     */
    protected array $keys = [];

    /**
     * Add an attribute to the collection
     */
    public function add(Attribute $attribute): self
    {
        $code = $attribute->getAttributeCode();
        $this->attributes[$code] = $attribute;
        $this->keys = array_keys($this->attributes);
        return $this;
    }

    /**
     * Get an attribute by code
     */
    public function get(string $code): ?Attribute
    {
        return $this->attributes[$code] ?? null;
    }

    /**
     * Check if an attribute exists
     */
    public function has(string $code): bool
    {
        return isset($this->attributes[$code]);
    }

    /**
     * Remove an attribute from the collection
     */
    public function remove(string $code): self
    {
        unset($this->attributes[$code]);
        $this->keys = array_keys($this->attributes);
        return $this;
    }

    /**
     * Get all attributes
     */
    public function getAll(): array
    {
        return $this->attributes;
    }

    /**
     * Get attributes by backend type
     */
    public function getByBackendType(string $type): array
    {
        return array_filter(
            $this->attributes,
            fn($attr) => $attr->getBackendType() === $type
        );
    }

    /**
     * Get searchable attributes
     */
    public function getSearchable(): array
    {
        return array_filter(
            $this->attributes,
            fn($attr) => $attr->isSearchable()
        );
    }

    /**
     * Get filterable attributes
     */
    public function getFilterable(): array
    {
        return array_filter(
            $this->attributes,
            fn($attr) => $attr->isFilterable()
        );
    }

    /**
     * Get required attributes
     */
    public function getRequired(): array
    {
        return array_filter(
            $this->attributes,
            fn($attr) => $attr->isRequired()
        );
    }

    /**
     * Get unique attributes
     */
    public function getUnique(): array
    {
        return array_filter(
            $this->attributes,
            fn($attr) => $attr->isUnique()
        );
    }

    /**
     * Get comparable attributes
     */
    public function getComparable(): array
    {
        return array_filter(
            $this->attributes,
            fn($attr) => $attr->isComparable()
        );
    }

    /**
     * Get attribute codes
     */
    public function getCodes(): array
    {
        return array_keys($this->attributes);
    }

    /**
     * Sort attributes by sort order
     */
    public function sort(): self
    {
        uasort($this->attributes, function($a, $b) {
            return $a->getSortOrder() <=> $b->getSortOrder();
        });
        $this->keys = array_keys($this->attributes);
        return $this;
    }

    /**
     * Countable implementation
     */
    public function count(): int
    {
        return count($this->attributes);
    }

    /**
     * Iterator implementation - rewind
     */
    public function rewind(): void
    {
        $this->position = 0;
        $this->keys = array_keys($this->attributes);
    }

    /**
     * Iterator implementation - current
     */
    public function current(): Attribute
    {
        $key = $this->keys[$this->position];
        return $this->attributes[$key];
    }

    /**
     * Iterator implementation - key
     */
    public function key(): string
    {
        return $this->keys[$this->position];
    }

    /**
     * Iterator implementation - next
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * Iterator implementation - valid
     */
    public function valid(): bool
    {
        return isset($this->keys[$this->position]);
    }

    /**
     * Convert collection to array
     */
    public function toArray(): array
    {
        return array_map(fn($attr) => $attr->toArray(), $this->attributes);
    }
}
