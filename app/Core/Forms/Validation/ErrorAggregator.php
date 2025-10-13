<?php
/**
 * ErrorAggregator Class
 * 
 * Collects and organizes validation errors from multiple sources,
 * providing methods for error formatting and retrieval.
 * 
 * @package Core\Forms\Validation
 * @since 2.0.0
 */

namespace Core\Forms\Validation;

class ErrorAggregator
{
    /**
     * @var array Errors indexed by field name
     */
    private array $errors = [];

    /**
     * @var array Warnings indexed by field name
     */
    private array $warnings = [];

    /**
     * @var array Form-level errors (not tied to specific fields)
     */
    private array $formErrors = [];

    /**
     * Add a ValidationResult to the aggregator
     * 
     * @param ValidationResult $result Validation result to add
     * @return self
     */
    public function addResult(ValidationResult $result): self
    {
        if ($result->isValid()) {
            return $this;
        }
        
        foreach ($result->getErrors() as $fieldName => $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $this->addError($fieldName, $error);
            }
        }
        
        foreach ($result->getWarnings() as $fieldName => $fieldWarnings) {
            foreach ($fieldWarnings as $warning) {
                $this->addWarning($fieldName, $warning);
            }
        }
        
        return $this;
    }

    /**
     * Add an error for a specific field
     * 
     * @param string $fieldName Field name
     * @param string $message Error message
     * @return self
     */
    public function addError(string $fieldName, string $message): self
    {
        if ($fieldName === '_form') {
            $this->formErrors[] = $message;
        } else {
            if (!isset($this->errors[$fieldName])) {
                $this->errors[$fieldName] = [];
            }
            $this->errors[$fieldName][] = $message;
        }
        
        return $this;
    }

    /**
     * Add a warning for a specific field
     * 
     * @param string $fieldName Field name
     * @param string $message Warning message
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
     * Check if there are any errors
     * 
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors) || !empty($this->formErrors);
    }

    /**
     * Get all errors
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
     * @param string $fieldName Field name
     * @return array
     */
    public function getFieldErrors(string $fieldName): array
    {
        return $this->errors[$fieldName] ?? [];
    }

    /**
     * Get first error for a field
     * 
     * @param string $fieldName Field name
     * @return string|null
     */
    public function getFirstFieldError(string $fieldName): ?string
    {
        $errors = $this->getFieldErrors($fieldName);
        return !empty($errors) ? $errors[0] : null;
    }

    /**
     * Get form-level errors
     * 
     * @return array
     */
    public function getFormErrors(): array
    {
        return $this->formErrors;
    }

    /**
     * Get all errors as flat array
     * 
     * @return array
     */
    public function getFlatErrors(): array
    {
        $flat = [];
        
        foreach ($this->errors as $fieldName => $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $flat[] = $error;
            }
        }
        
        foreach ($this->formErrors as $error) {
            $flat[] = $error;
        }
        
        return $flat;
    }

    /**
     * Get all warnings
     * 
     * @return array
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Check if field has errors
     * 
     * @param string $fieldName Field name
     * @return bool
     */
    public function hasFieldErrors(string $fieldName): bool
    {
        return isset($this->errors[$fieldName]) && !empty($this->errors[$fieldName]);
    }

    /**
     * Get error count
     * 
     * @return int
     */
    public function getErrorCount(): int
    {
        $count = count($this->formErrors);
        
        foreach ($this->errors as $fieldErrors) {
            $count += count($fieldErrors);
        }
        
        return $count;
    }

    /**
     * Clear all errors
     * 
     * @return self
     */
    public function clear(): self
    {
        $this->errors = [];
        $this->warnings = [];
        $this->formErrors = [];
        
        return $this;
    }

    /**
     * Create a ValidationResult from aggregated errors
     * 
     * @return ValidationResult
     */
    public function toValidationResult(): ValidationResult
    {
        if (!$this->hasErrors()) {
            return ValidationResult::success();
        }
        
        $allErrors = $this->errors;
        
        if (!empty($this->formErrors)) {
            $allErrors['_form'] = $this->formErrors;
        }
        
        return ValidationResult::failure($allErrors);
    }

    /**
     * Format errors for display
     * 
     * @param string $format Format: 'list', 'grouped', 'json'
     * @return mixed
     */
    public function format(string $format = 'list'): mixed
    {
        switch ($format) {
            case 'list':
                return $this->formatAsList();
                
            case 'grouped':
                return $this->formatAsGrouped();
                
            case 'json':
                return $this->formatAsJson();
                
            default:
                return $this->errors;
        }
    }

    /**
     * Format errors as flat list
     * 
     * @return array
     */
    private function formatAsList(): array
    {
        return $this->getFlatErrors();
    }

    /**
     * Format errors grouped by field
     * 
     * @return array
     */
    private function formatAsGrouped(): array
    {
        $grouped = $this->errors;
        
        if (!empty($this->formErrors)) {
            $grouped['_form'] = $this->formErrors;
        }
        
        return $grouped;
    }

    /**
     * Format errors as JSON
     * 
     * @return string
     */
    private function formatAsJson(): string
    {
        return json_encode([
            'errors' => $this->errors,
            'formErrors' => $this->formErrors,
            'warnings' => $this->warnings,
            'count' => $this->getErrorCount()
        ]);
    }

    /**
     * Format errors as HTML list
     * 
     * @param string $className CSS class for the list
     * @return string
     */
    public function toHtml(string $className = 'error-list'): string
    {
        if (!$this->hasErrors()) {
            return '';
        }
        
        $html = [];
        $html[] = sprintf('<ul class="%s">', htmlspecialchars($className));
        
        foreach ($this->getFlatErrors() as $error) {
            $html[] = sprintf('<li>%s</li>', htmlspecialchars($error));
        }
        
        $html[] = '</ul>';
        
        return implode("\n", $html);
    }

    /**
     * Convert to array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'errors' => $this->errors,
            'formErrors' => $this->formErrors,
            'warnings' => $this->warnings,
            'hasErrors' => $this->hasErrors(),
            'errorCount' => $this->getErrorCount()
        ];
    }
}
