<?php
/**
 * ValidationResult Class
 * 
 * Represents the outcome of a validation operation, storing
 * validation status, error messages, and metadata.
 * 
 * @package Core\Forms\Validation
 * @since 2.0.0
 */

namespace Core\Forms\Validation;

class ValidationResult
{
    /**
     * @var bool Whether validation passed
     */
    private bool $isValid;

    /**
     * @var array Error messages indexed by field name
     */
    private array $errors = [];

    /**
     * @var array Warning messages (non-blocking)
     */
    private array $warnings = [];

    /**
     * @var array Metadata about the validation
     */
    private array $metadata = [];

    /**
     * Create a new validation result
     * 
     * @param bool $isValid Whether validation passed
     * @param array $errors Error messages
     * @param array $warnings Warning messages
     * @param array $metadata Additional metadata
     */
    public function __construct(
        bool $isValid = true,
        array $errors = [],
        array $warnings = [],
        array $metadata = []
    ) {
        $this->isValid = $isValid;
        $this->errors = $errors;
        $this->warnings = $warnings;
        $this->metadata = $metadata;
    }

    /**
     * Create a successful validation result
     * 
     * @return self
     */
    public static function success(): self
    {
        return new self(true);
    }

    /**
     * Create a failed validation result
     * 
     * @param array $errors Error messages
     * @return self
     */
    public static function failure(array $errors): self
    {
        return new self(false, $errors);
    }

    /**
     * Check if validation passed
     * 
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * Check if validation failed
     * 
     * @return bool
     */
    public function isFailed(): bool
    {
        return !$this->isValid;
    }

    /**
     * Get all error messages
     * 
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get errors for a specific field
     * 
     * @param string $fieldName The field name
     * @return array
     */
    public function getFieldErrors(string $fieldName): array
    {
        return $this->errors[$fieldName] ?? [];
    }

    /**
     * Add an error message
     * 
     * @param string $fieldName The field name
     * @param string $message The error message
     * @return self
     */
    public function addError(string $fieldName, string $message): self
    {
        if (!isset($this->errors[$fieldName])) {
            $this->errors[$fieldName] = [];
        }
        $this->errors[$fieldName][] = $message;
        $this->isValid = false;
        
        return $this;
    }

    /**
     * Check if there are errors for a specific field
     * 
     * @param string $fieldName The field name
     * @return bool
     */
    public function hasFieldErrors(string $fieldName): bool
    {
        return isset($this->errors[$fieldName]) && !empty($this->errors[$fieldName]);
    }

    /**
     * Get all warning messages
     * 
     * @return array
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Add a warning message
     * 
     * @param string $fieldName The field name
     * @param string $message The warning message
     * @return self
     */
    public function addWarning(string $fieldName, string $message): self
    {
        if (!isset($this->warnings[$fieldName])) {
            $this->warnings[$fieldName] = [];
        }
        $this->warnings[$fieldName][] = $message;
        
        return $this;
    }

    /**
     * Get metadata
     * 
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Set metadata value
     * 
     * @param string $key Metadata key
     * @param mixed $value Metadata value
     * @return self
     */
    public function setMetadata(string $key, mixed $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * Get metadata value
     * 
     * @param string $key Metadata key
     * @param mixed $default Default value
     * @return mixed
     */
    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Merge another validation result into this one
     * 
     * @param ValidationResult $result The result to merge
     * @return self
     */
    public function merge(ValidationResult $result): self
    {
        if ($result->isFailed()) {
            $this->isValid = false;
        }

        foreach ($result->getErrors() as $fieldName => $errors) {
            foreach ($errors as $error) {
                $this->addError($fieldName, $error);
            }
        }

        foreach ($result->getWarnings() as $fieldName => $warnings) {
            foreach ($warnings as $warning) {
                $this->addWarning($fieldName, $warning);
            }
        }

        $this->metadata = array_merge($this->metadata, $result->getMetadata());

        return $this;
    }

    /**
     * Get first error message for a field
     * 
     * @param string $fieldName The field name
     * @return string|null
     */
    public function getFirstFieldError(string $fieldName): ?string
    {
        $errors = $this->getFieldErrors($fieldName);
        return !empty($errors) ? $errors[0] : null;
    }

    /**
     * Get all errors as flat array
     * 
     * @return array
     */
    public function getFlatErrors(): array
    {
        $flat = [];
        foreach ($this->errors as $fieldName => $errors) {
            foreach ($errors as $error) {
                $flat[] = $error;
            }
        }
        return $flat;
    }

    /**
     * Convert to array representation
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'isValid' => $this->isValid,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Convert to JSON string
     * 
     * @param int $options JSON encode options
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}
