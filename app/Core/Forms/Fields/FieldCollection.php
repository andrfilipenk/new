<?php
/**
 * FieldCollection Class
 * 
 * Manages a collection of form fields with support for ordering,
 * iteration, and field relationship management.
 * 
 * @package Core\Forms\Fields
 * @since 2.0.0
 */

namespace Core\Forms\Fields;

use ArrayIterator;
use IteratorAggregate;
use Countable;

class FieldCollection implements IteratorAggregate, Countable
{
    /**
     * @var array<string, FieldInterface> Fields indexed by name
     */
    private array $fields = [];

    /**
     * @var array Field order (names)
     */
    private array $order = [];

    /**
     * Add a field to the collection
     * 
     * @param FieldInterface $field The field to add
     * @param string|null $afterField Add after this field name
     * @return self
     */
    public function add(FieldInterface $field, ?string $afterField = null): self
    {
        $name = $field->getName();
        $this->fields[$name] = $field;
        
        // Handle field ordering
        if ($afterField !== null && isset($this->fields[$afterField])) {
            $this->insertAfter($name, $afterField);
        } else {
            if (!in_array($name, $this->order)) {
                $this->order[] = $name;
            }
        }
        
        return $this;
    }

    /**
     * Get a field by name
     * 
     * @param string $name Field name
     * @return FieldInterface|null
     */
    public function get(string $name): ?FieldInterface
    {
        return $this->fields[$name] ?? null;
    }

    /**
     * Check if field exists
     * 
     * @param string $name Field name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->fields[$name]);
    }

    /**
     * Remove a field
     * 
     * @param string $name Field name
     * @return self
     */
    public function remove(string $name): self
    {
        unset($this->fields[$name]);
        $this->order = array_values(array_filter($this->order, fn($n) => $n !== $name));
        
        return $this;
    }

    /**
     * Get all fields in order
     * 
     * @return array<FieldInterface>
     */
    public function all(): array
    {
        $orderedFields = [];
        foreach ($this->order as $name) {
            if (isset($this->fields[$name])) {
                $orderedFields[$name] = $this->fields[$name];
            }
        }
        return $orderedFields;
    }

    /**
     * Get field names
     * 
     * @return array
     */
    public function getNames(): array
    {
        return $this->order;
    }

    /**
     * Get count of fields
     * 
     * @return int
     */
    public function count(): int
    {
        return count($this->fields);
    }

    /**
     * Get iterator for fields
     * 
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->all());
    }

    /**
     * Move field to a specific position
     * 
     * @param string $fieldName Field to move
     * @param int $position New position (0-based)
     * @return self
     */
    public function moveTo(string $fieldName, int $position): self
    {
        if (!isset($this->fields[$fieldName])) {
            return $this;
        }
        
        // Remove from current position
        $this->order = array_values(array_filter($this->order, fn($n) => $n !== $fieldName));
        
        // Insert at new position
        array_splice($this->order, $position, 0, $fieldName);
        
        return $this;
    }

    /**
     * Insert field after another field
     * 
     * @param string $fieldName Field to insert
     * @param string $afterField Insert after this field
     * @return self
     */
    public function insertAfter(string $fieldName, string $afterField): self
    {
        $position = array_search($afterField, $this->order);
        
        if ($position === false) {
            return $this;
        }
        
        // Remove from current position if exists
        $this->order = array_values(array_filter($this->order, fn($n) => $n !== $fieldName));
        
        // Insert after target field
        array_splice($this->order, $position + 1, 0, $fieldName);
        
        return $this;
    }

    /**
     * Insert field before another field
     * 
     * @param string $fieldName Field to insert
     * @param string $beforeField Insert before this field
     * @return self
     */
    public function insertBefore(string $fieldName, string $beforeField): self
    {
        $position = array_search($beforeField, $this->order);
        
        if ($position === false) {
            return $this;
        }
        
        // Remove from current position if exists
        $this->order = array_values(array_filter($this->order, fn($n) => $n !== $fieldName));
        
        // Insert before target field
        array_splice($this->order, $position, 0, $fieldName);
        
        return $this;
    }

    /**
     * Filter fields by callback
     * 
     * @param callable $callback Filter function
     * @return array<FieldInterface>
     */
    public function filter(callable $callback): array
    {
        return array_filter($this->all(), $callback);
    }

    /**
     * Map fields using callback
     * 
     * @param callable $callback Map function
     * @return array
     */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->all());
    }

    /**
     * Get required fields
     * 
     * @return array<FieldInterface>
     */
    public function getRequired(): array
    {
        return $this->filter(fn($field) => $field->isRequired());
    }

    /**
     * Get fields by type
     * 
     * @param string $type Field type
     * @return array<FieldInterface>
     */
    public function getByType(string $type): array
    {
        return $this->filter(fn($field) => $field->getType() === $type);
    }

    /**
     * Set values for multiple fields
     * 
     * @param array $values Values indexed by field name
     * @return self
     */
    public function setValues(array $values): self
    {
        foreach ($values as $name => $value) {
            if (isset($this->fields[$name])) {
                $this->fields[$name]->setValue($value);
            }
        }
        
        return $this;
    }

    /**
     * Get values from all fields
     * 
     * @return array
     */
    public function getValues(): array
    {
        $values = [];
        foreach ($this->all() as $name => $field) {
            $values[$name] = $field->getValue();
        }
        return $values;
    }

    /**
     * Clear all fields
     * 
     * @return self
     */
    public function clear(): self
    {
        $this->fields = [];
        $this->order = [];
        
        return $this;
    }

    /**
     * Check if collection is empty
     * 
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->fields);
    }

    /**
     * Convert to array representation
     * 
     * @return array
     */
    public function toArray(): array
    {
        $array = [];
        foreach ($this->all() as $name => $field) {
            $array[$name] = [
                'name' => $field->getName(),
                'type' => $field->getType(),
                'value' => $field->getValue(),
                'label' => $field->getLabel(),
                'required' => $field->isRequired(),
                'attributes' => $field->getAttributes(),
                'validationRules' => $field->getValidationRules(),
            ];
        }
        return $array;
    }
}
