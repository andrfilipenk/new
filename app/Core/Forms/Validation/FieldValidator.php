<?php
namespace Core\Forms\Validation;

use Core\Forms\Fields\FieldInterface;

class FieldValidator
{
    /**
     * @var array Built-in validation rules
     */
    private static array $builtInRules = [
        'required', 'email', 'url', 'numeric', 'integer', 'alpha', 'alphanumeric',
        'min', 'max', 'minlength', 'maxlength', 'pattern', 'in', 'not_in',
        'confirmed', 'same', 'different', 'date', 'before', 'after'
    ];

    /**
     * @var array Custom validator callbacks
     */
    private static array $customValidators = [];

    /**
     * @var array Default error messages
     */
    private static array $defaultMessages = [
        'required' => 'This field is required.',
        'email' => 'Please enter a valid email address.',
        'url' => 'Please enter a valid URL.',
        'numeric' => 'This field must be a number.',
        'integer' => 'This field must be an integer.',
        'alpha' => 'This field may only contain letters.',
        'alphanumeric' => 'This field may only contain letters and numbers.',
        'min' => 'Value must be at least :min.',
        'max' => 'Value must not exceed :max.',
        'minlength' => 'Must be at least :minlength characters.',
        'maxlength' => 'Must not exceed :maxlength characters.',
        'pattern' => 'Value does not match the required pattern.',
        'in' => 'Invalid selection.',
        'not_in' => 'Invalid selection.',
        'confirmed' => 'Confirmation does not match.',
        'same' => 'This field must match :other.',
        'different' => 'This field must be different from :other.',
        'date' => 'Please enter a valid date.',
        'before' => 'Date must be before :date.',
        'after' => 'Date must be after :date.',
    ];

    /**
     * Validate a field
     * 
     * @param FieldInterface $field Field to validate
     * @param mixed $value Value to validate
     * @param array $context Additional validation context (other field values)
     * @return ValidationResult
     */
    public function validate(FieldInterface $field, mixed $value, array $context = []): ValidationResult
    {
        $result = ValidationResult::success();
        $rules = $field->getValidationRules();
        
        // Skip validation if field is not required and value is empty
        if (!$field->isRequired() && $this->isEmpty($value)) {
            return $result;
        }
        
        foreach ($rules as $ruleName => $ruleConfig) {
            $validationResult = $this->validateRule($field->getName(), $value, $ruleName, $ruleConfig, $context);
            
            if ($validationResult->isFailed()) {
                $result->merge($validationResult);
            }
        }
        
        return $result;
    }

    /**
     * Validate a single rule
     * 
     * @param string $fieldName Field name
     * @param mixed $value Value to validate
     * @param string $ruleName Rule name
     * @param mixed $ruleConfig Rule configuration
     * @param array $context Validation context
     * @return ValidationResult
     */
    private function validateRule(string $fieldName, mixed $value, string $ruleName, mixed $ruleConfig, array $context): ValidationResult
    {
        // Check custom validators first
        if (isset(self::$customValidators[$ruleName])) {
            return $this->validateCustomRule($fieldName, $value, $ruleName, $ruleConfig, $context);
        }
        
        // Validate built-in rules
        $methodName = 'validate' . ucfirst($ruleName);
        
        if (method_exists($this, $methodName)) {
            $isValid = $this->$methodName($value, $ruleConfig, $context);
            
            if (!$isValid) {
                $message = $this->getErrorMessage($ruleName, $ruleConfig);
                return ValidationResult::failure([$fieldName => [$message]]);
            }
            
            return ValidationResult::success();
        }
        
        // Unknown rule - return success to avoid breaking validation
        return ValidationResult::success();
    }

    /**
     * Validate custom rule
     * 
     * @param string $fieldName Field name
     * @param mixed $value Value to validate
     * @param string $ruleName Rule name
     * @param mixed $ruleConfig Rule configuration
     * @param array $context Validation context
     * @return ValidationResult
     */
    private function validateCustomRule(string $fieldName, mixed $value, string $ruleName, mixed $ruleConfig, array $context): ValidationResult
    {
        $validator = self::$customValidators[$ruleName];
        $isValid = $validator($value, $ruleConfig, $context);
        
        if (!$isValid) {
            $message = is_array($ruleConfig) && isset($ruleConfig['message']) 
                ? $ruleConfig['message'] 
                : "Validation failed for rule: {$ruleName}";
                
            return ValidationResult::failure([$fieldName => [$message]]);
        }
        
        return ValidationResult::success();
    }

    /**
     * Check if value is empty
     * 
     * @param mixed $value Value to check
     * @return bool
     */
    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && empty($value));
    }

    /**
     * Get error message for a rule
     * 
     * @param string $ruleName Rule name
     * @param mixed $ruleConfig Rule configuration
     * @return string
     */
    private function getErrorMessage(string $ruleName, mixed $ruleConfig): string
    {
        // Check for custom message in config
        if (is_array($ruleConfig) && isset($ruleConfig['message'])) {
            return $ruleConfig['message'];
        }
        
        $message = self::$defaultMessages[$ruleName] ?? 'Validation failed.';
        
        // Replace placeholders
        if (is_array($ruleConfig)) {
            foreach ($ruleConfig as $key => $val) {
                $message = str_replace(":{$key}", $val, $message);
            }
        } else {
            $message = str_replace(":{$ruleName}", $ruleConfig, $message);
        }
        
        return $message;
    }

    /**
     * Register a custom validator
     * 
     * @param string $name Validator name
     * @param callable $callback Validator callback
     * @return void
     */
    public static function registerValidator(string $name, callable $callback): void
    {
        self::$customValidators[$name] = $callback;
    }

    /**
     * Set custom error message for a rule
     * 
     * @param string $ruleName Rule name
     * @param string $message Error message
     * @return void
     */
    public static function setMessage(string $ruleName, string $message): void
    {
        self::$defaultMessages[$ruleName] = $message;
    }

    // ==================== Built-in Validation Rules ====================

    /**
     * Validate required field
     */
    private function validateRequired(mixed $value, mixed $config, array $context): bool
    {
        return !$this->isEmpty($value);
    }

    /**
     * Validate email format
     */
    private function validateEmail(mixed $value, mixed $config, array $context): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate URL format
     */
    private function validateUrl(mixed $value, mixed $config, array $context): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate numeric value
     */
    private function validateNumeric(mixed $value, mixed $config, array $context): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }
        return is_numeric($value);
    }

    /**
     * Validate integer value
     */
    private function validateInteger(mixed $value, mixed $config, array $context): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Validate alphabetic characters only
     */
    private function validateAlpha(mixed $value, mixed $config, array $context): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }
        return preg_match('/^[a-zA-Z]+$/', $value) === 1;
    }

    /**
     * Validate alphanumeric characters only
     */
    private function validateAlphanumeric(mixed $value, mixed $config, array $context): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }
        return preg_match('/^[a-zA-Z0-9]+$/', $value) === 1;
    }

    /**
     * Validate minimum value
     */
    private function validateMin(mixed $value, mixed $config, array $context): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }
        
        $min = is_array($config) ? ($config['value'] ?? $config['min'] ?? 0) : $config;
        
        if (is_numeric($value)) {
            return (float)$value >= (float)$min;
        }
        
        return false;
    }

    /**
     * Validate maximum value
     */
    private function validateMax(mixed $value, mixed $config, array $context): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }
        
        $max = is_array($config) ? ($config['value'] ?? $config['max'] ?? 0) : $config;
        
        if (is_numeric($value)) {
            return (float)$value <= (float)$max;
        }
        
        return false;
    }

    /**
     * Validate minimum string length
     */
    private function validateMinlength(mixed $value, mixed $config, array $context): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }
        
        $minLength = is_array($config) ? ($config['value'] ?? $config['minlength'] ?? 0) : $config;
        return mb_strlen((string)$value) >= (int)$minLength;
    }

    /**
     * Validate maximum string length
     */
    private function validateMaxlength(mixed $value, mixed $config, array $context): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }
        
        $maxLength = is_array($config) ? ($config['value'] ?? $config['maxlength'] ?? 0) : $config;
        return mb_strlen((string)$value) <= (int)$maxLength;
    }

    /**
     * Validate pattern (regex)
     */
    private function validatePattern(mixed $value, mixed $config, array $context): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }
        
        $pattern = is_array($config) ? ($config['value'] ?? $config['pattern'] ?? '') : $config;
        return preg_match('/' . $pattern . '/', $value) === 1;
    }

    /**
     * Validate value is in array
     */
    private function validateIn(mixed $value, mixed $config, array $context): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }
        
        $allowed = is_array($config) ? ($config['values'] ?? $config) : [$config];
        return in_array($value, $allowed, true);
    }

    /**
     * Validate value is not in array
     */
    private function validateNot_in(mixed $value, mixed $config, array $context): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }
        
        $forbidden = is_array($config) ? ($config['values'] ?? $config) : [$config];
        return !in_array($value, $forbidden, true);
    }

    /**
     * Validate confirmation field matches
     */
    private function validateConfirmed(mixed $value, mixed $config, array $context): bool
    {
        $confirmationField = is_array($config) ? ($config['field'] ?? null) : null;
        
        if (!$confirmationField) {
            return true;
        }
        
        $confirmationValue = $context[$confirmationField] ?? null;
        return $value === $confirmationValue;
    }

    /**
     * Validate field matches another field
     */
    private function validateSame(mixed $value, mixed $config, array $context): bool
    {
        $otherField = is_array($config) ? ($config['field'] ?? $config['other'] ?? null) : $config;
        
        if (!$otherField) {
            return true;
        }
        
        $otherValue = $context[$otherField] ?? null;
        return $value === $otherValue;
    }

    /**
     * Validate field is different from another field
     */
    private function validateDifferent(mixed $value, mixed $config, array $context): bool
    {
        $otherField = is_array($config) ? ($config['field'] ?? $config['other'] ?? null) : $config;
        
        if (!$otherField) {
            return true;
        }
        
        $otherValue = $context[$otherField] ?? null;
        return $value !== $otherValue;
    }

    /**
     * Validate date format
     */
    private function validateDate(mixed $value, mixed $config, array $context): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }
        
        return strtotime($value) !== false;
    }

    /**
     * Validate date is before another date
     */
    private function validateBefore(mixed $value, mixed $config, array $context): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }
        
        $compareDate = is_array($config) ? ($config['date'] ?? null) : $config;
        
        if (!$compareDate) {
            return true;
        }
        
        $valueTime = strtotime($value);
        $compareTime = strtotime($compareDate);
        
        return $valueTime !== false && $compareTime !== false && $valueTime < $compareTime;
    }

    /**
     * Validate date is after another date
     */
    private function validateAfter(mixed $value, mixed $config, array $context): bool
    {
        if ($this->isEmpty($value)) {
            return true;
        }
        
        $compareDate = is_array($config) ? ($config['date'] ?? null) : $config;
        
        if (!$compareDate) {
            return true;
        }
        
        $valueTime = strtotime($value);
        $compareTime = strtotime($compareDate);
        
        return $valueTime !== false && $compareTime !== false && $valueTime > $compareTime;
    }
}
