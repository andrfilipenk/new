<?php
// app/Core/Dto/AbstractDto.php
namespace Core\Dto;

/**
 * Abstract Data Transfer Object
 * 
 * Base implementation for DTOs with common functionality
 */
abstract class AbstractDto implements DtoInterface
{
    /**
     * Create DTO from array with automatic property mapping
     */
    public static function fromArray(array $data): static
    {
        $dto = new static();
        
        foreach ($data as $key => $value) {
            if (property_exists($dto, $key)) {
                $dto->$key = $value;
            }
        }
        
        return $dto;
    }

    /**
     * Convert DTO to array
     */
    public function toArray(): array
    {
        $result = [];
        $reflection = new \ReflectionClass($this);
        
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();
            $result[$name] = $this->$name;
        }
        
        return $result;
    }

    /**
     * Default validation (override in child classes)
     */
    public function validate(): array
    {
        return [];
    }

    /**
     * Check if DTO has required fields
     */
    protected function validateRequired(array $requiredFields): array
    {
        $errors = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($this->$field) || $this->$field === null || $this->$field === '') {
                $errors[$field] = "Field '{$field}' is required";
            }
        }
        
        return $errors;
    }

    /**
     * Validate field types
     */
    protected function validateTypes(array $typeMap): array
    {
        $errors = [];
        
        foreach ($typeMap as $field => $expectedType) {
            if (isset($this->$field) && $this->$field !== null) {
                $actualType = gettype($this->$field);
                
                if ($actualType !== $expectedType) {
                    $errors[$field] = "Field '{$field}' must be of type {$expectedType}, {$actualType} given";
                }
            }
        }
        
        return $errors;
    }

    /**
     * Get validation rules for specific DTO (override in child classes)
     */
    protected function getValidationRules(): array
    {
        return [];
    }

    /**
     * Magic method for property access
     */
    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        
        throw new \InvalidArgumentException("Property '{$name}' does not exist");
    }

    /**
     * Magic method for property setting
     */
    public function __set($name, $value)
    {
        if (property_exists($this, $name)) {
            $this->$name = $value;
            return;
        }
        
        throw new \InvalidArgumentException("Property '{$name}' does not exist");
    }

    /**
     * Magic method for checking if property is set
     */
    public function __isset($name)
    {
        return property_exists($this, $name) && isset($this->$name);
    }
}