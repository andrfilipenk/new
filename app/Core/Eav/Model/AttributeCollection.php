<?php
// app/Core/Eav/Model/AttributeCollection.php
namespace Core\Eav\Model;

use ArrayIterator;
use IteratorAggregate;
use Countable;

/**
 * Collection of attributes
 */
class AttributeCollection implements IteratorAggregate, Countable
{
    private array $attributes = [];

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $attribute) {
            $this->add($attribute);
        }
    }

    public function add(Attribute $attribute): void
    {
        $this->attributes[$attribute->getCode()] = $attribute;
    }

    public function get(string $code): ?Attribute
    {
        return $this->attributes[$code] ?? null;
    }

    public function has(string $code): bool
    {
        return isset($this->attributes[$code]);
    }

    public function remove(string $code): void
    {
        unset($this->attributes[$code]);
    }

    public function all(): array
    {
        return $this->attributes;
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->attributes);
    }

    public function count(): int
    {
        return count($this->attributes);
    }

    /**
     * Get attributes grouped by backend type
     */
    public function groupByBackendType(): array
    {
        $grouped = [];
        foreach ($this->attributes as $attribute) {
            $type = $attribute->getBackendType();
            $grouped[$type][] = $attribute;
        }
        return $grouped;
    }

    /**
     * Filter attributes by a callable
     */
    public function filter(callable $callback): AttributeCollection
    {
        return new self(array_filter($this->attributes, $callback));
    }
}
