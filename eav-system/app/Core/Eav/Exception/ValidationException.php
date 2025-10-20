<?php
// app/Eav/Exception/ValidationException.php
namespace Eav\Exception;

/**
 * Validation Exception
 * 
 * Thrown when attribute value validation fails.
 */
class ValidationException extends EavException
{
    /**
     * Validation errors
     */
    protected array $validationErrors = [];

    /**
     * Set validation errors
     */
    public function setValidationErrors(array $errors): self
    {
        $this->validationErrors = $errors;
        return $this;
    }

    /**
     * Get validation errors
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Add a validation error
     */
    public function addValidationError(string $field, string $message): self
    {
        if (!isset($this->validationErrors[$field])) {
            $this->validationErrors[$field] = [];
        }
        $this->validationErrors[$field][] = $message;
        return $this;
    }

    /**
     * Create exception for required field
     */
    public static function requiredField(string $attributeCode): self
    {
        return (new self("Attribute '{$attributeCode}' is required"))
            ->addValidationError($attributeCode, 'This field is required');
    }

    /**
     * Create exception for invalid type
     */
    public static function invalidType(string $attributeCode, string $expected, $actual): self
    {
        $actualType = gettype($actual);
        return (new self("Attribute '{$attributeCode}' expects {$expected}, got {$actualType}"))
            ->addValidationError($attributeCode, "Expected {$expected}, got {$actualType}");
    }

    /**
     * Create exception for unique constraint violation
     */
    public static function uniqueViolation(string $attributeCode, $value): self
    {
        return (new self("Attribute '{$attributeCode}' must be unique, value already exists"))
            ->addValidationError($attributeCode, 'This value already exists')
            ->setContext(['attribute' => $attributeCode, 'value' => $value]);
    }

    /**
     * Create exception for multiple validation failures
     */
    public static function multipleErrors(array $errors): self
    {
        return (new self('Multiple validation errors occurred'))
            ->setValidationErrors($errors);
    }
}
