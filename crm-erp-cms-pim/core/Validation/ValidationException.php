<?php

namespace Core\Validation;

/**
 * Validation Exception
 * 
 * Thrown when validation fails
 */
class ValidationException extends \Exception
{
    /**
     * @var array Validation errors
     */
    protected array $errors;
    
    /**
     * Constructor
     */
    public function __construct(string $message = 'Validation failed', array $errors = [])
    {
        parent::__construct($message);
        $this->errors = $errors;
    }
    
    /**
     * Get validation errors
     */
    public function errors(): array
    {
        return $this->errors;
    }
    
    /**
     * Get first error for a field
     */
    public function first($field = null): ?string
    {
        if ($field === null) {
            // Get first error from all fields
            foreach ($this->errors as $errors) {
                return $errors[0] ?? null;
            }
            return null;
        }
        
        return $this->errors[$field][0] ?? null;
    }
}
