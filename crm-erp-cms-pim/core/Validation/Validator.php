<?php

namespace Core\Validation;

use Core\Database\DB;

/**
 * Validator
 * 
 * Declarative validation with built-in rules
 */
class Validator
{
    /**
     * @var array Data to validate
     */
    protected array $data;
    
    /**
     * @var array Validation rules
     */
    protected array $rules;
    
    /**
     * @var array Custom error messages
     */
    protected array $messages;
    
    /**
     * @var array Validation errors
     */
    protected array $errors = [];
    
    /**
     * @var array Custom validation rules
     */
    protected static array $customRules = [];
    
    /**
     * Constructor
     */
    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;
    }
    
    /**
     * Create validator instance
     */
    public static function make(array $data, array $rules, array $messages = []): static
    {
        return new static($data, $rules, $messages);
    }
    
    /**
     * Validate data
     */
    public function validate(): array
    {
        if ($this->fails()) {
            throw new ValidationException('Validation failed', $this->errors);
        }
        
        return $this->validated();
    }
    
    /**
     * Check if validation fails
     */
    public function fails(): bool
    {
        $this->errors = [];
        
        foreach ($this->rules as $field => $rules) {
            $this->validateField($field, $rules);
        }
        
        return !empty($this->errors);
    }
    
    /**
     * Check if validation passes
     */
    public function passes(): bool
    {
        return !$this->fails();
    }
    
    /**
     * Get validation errors
     */
    public function errors(): array
    {
        return $this->errors;
    }
    
    /**
     * Get validated data
     */
    public function validated(): array
    {
        $validated = [];
        
        foreach (array_keys($this->rules) as $field) {
            if (isset($this->data[$field])) {
                $validated[$field] = $this->data[$field];
            }
        }
        
        return $validated;
    }
    
    /**
     * Validate a single field
     */
    protected function validateField(string $field, string|array $rules): void
    {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }
        
        $value = $this->data[$field] ?? null;
        
        foreach ($rules as $rule) {
            $this->applyRule($field, $value, $rule);
        }
    }
    
    /**
     * Apply a validation rule
     */
    protected function applyRule(string $field, $value, string $rule): void
    {
        // Parse rule and parameters
        $parameters = [];
        
        if (str_contains($rule, ':')) {
            [$rule, $params] = explode(':', $rule, 2);
            $parameters = explode(',', $params);
        }
        
        $method = 'validate' . str_replace('_', '', ucwords($rule, '_'));
        
        // Check custom rules first
        if (isset(static::$customRules[$rule])) {
            $callback = static::$customRules[$rule];
            
            if (!$callback($value, $parameters, $this->data)) {
                $this->addError($field, $rule, $parameters);
            }
            
            return;
        }
        
        // Check built-in rules
        if (method_exists($this, $method)) {
            if (!$this->$method($field, $value, $parameters)) {
                $this->addError($field, $rule, $parameters);
            }
        } else {
            throw new \Exception("Validation rule not found: {$rule}");
        }
    }
    
    /**
     * Add validation error
     */
    protected function addError(string $field, string $rule, array $parameters = []): void
    {
        $message = $this->getMessage($field, $rule, $parameters);
        
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $this->errors[$field][] = $message;
    }
    
    /**
     * Get error message
     */
    protected function getMessage(string $field, string $rule, array $parameters): string
    {
        // Check custom messages
        $key = "{$field}.{$rule}";
        if (isset($this->messages[$key])) {
            return $this->replaceParameters($this->messages[$key], $field, $parameters);
        }
        
        // Use default messages
        $messages = [
            'required' => 'The :field field is required.',
            'email' => 'The :field must be a valid email address.',
            'min' => 'The :field must be at least :min characters.',
            'max' => 'The :field must not exceed :max characters.',
            'numeric' => 'The :field must be numeric.',
            'integer' => 'The :field must be an integer.',
            'alpha' => 'The :field may only contain letters.',
            'alpha_num' => 'The :field may only contain letters and numbers.',
            'alpha_dash' => 'The :field may only contain letters, numbers, dashes and underscores.',
            'url' => 'The :field must be a valid URL.',
            'ip' => 'The :field must be a valid IP address.',
            'date' => 'The :field must be a valid date.',
            'boolean' => 'The :field must be true or false.',
            'array' => 'The :field must be an array.',
            'string' => 'The :field must be a string.',
            'same' => 'The :field must match :other.',
            'different' => 'The :field must be different from :other.',
            'in' => 'The selected :field is invalid.',
            'not_in' => 'The selected :field is invalid.',
            'regex' => 'The :field format is invalid.',
            'unique' => 'The :field has already been taken.',
            'exists' => 'The selected :field is invalid.',
            'confirmed' => 'The :field confirmation does not match.',
        ];
        
        $template = $messages[$rule] ?? 'The :field field is invalid.';
        
        return $this->replaceParameters($template, $field, $parameters);
    }
    
    /**
     * Replace message parameters
     */
    protected function replaceParameters(string $message, string $field, array $parameters): string
    {
        $message = str_replace(':field', $field, $message);
        
        if (!empty($parameters)) {
            $message = str_replace(':min', $parameters[0] ?? '', $message);
            $message = str_replace(':max', $parameters[0] ?? '', $message);
            $message = str_replace(':other', $parameters[0] ?? '', $message);
        }
        
        return $message;
    }
    
    /**
     * Register custom validation rule
     */
    public static function extend(string $rule, callable $callback): void
    {
        static::$customRules[$rule] = $callback;
    }
    
    // =========================================================================
    // Built-in Validation Rules
    // =========================================================================
    
    protected function validateRequired(string $field, $value): bool
    {
        if ($value === null) {
            return false;
        }
        
        if (is_string($value) && trim($value) === '') {
            return false;
        }
        
        if (is_array($value) && empty($value)) {
            return false;
        }
        
        return true;
    }
    
    protected function validateEmail(string $field, $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    protected function validateMin(string $field, $value, array $parameters): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        $min = $parameters[0] ?? 0;
        
        if (is_numeric($value)) {
            return $value >= $min;
        }
        
        return mb_strlen($value) >= $min;
    }
    
    protected function validateMax(string $field, $value, array $parameters): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        $max = $parameters[0] ?? 0;
        
        if (is_numeric($value)) {
            return $value <= $max;
        }
        
        return mb_strlen($value) <= $max;
    }
    
    protected function validateNumeric(string $field, $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        return is_numeric($value);
    }
    
    protected function validateInteger(string $field, $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
    
    protected function validateAlpha(string $field, $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        return preg_match('/^[a-zA-Z]+$/', $value) === 1;
    }
    
    protected function validateAlphaNum(string $field, $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        return preg_match('/^[a-zA-Z0-9]+$/', $value) === 1;
    }
    
    protected function validateAlphaDash(string $field, $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        return preg_match('/^[a-zA-Z0-9_-]+$/', $value) === 1;
    }
    
    protected function validateUrl(string $field, $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
    
    protected function validateIp(string $field, $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }
    
    protected function validateDate(string $field, $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        return strtotime($value) !== false;
    }
    
    protected function validateBoolean(string $field, $value): bool
    {
        return in_array($value, [true, false, 0, 1, '0', '1'], true);
    }
    
    protected function validateArray(string $field, $value): bool
    {
        return is_array($value);
    }
    
    protected function validateString(string $field, $value): bool
    {
        return is_string($value);
    }
    
    protected function validateSame(string $field, $value, array $parameters): bool
    {
        $other = $parameters[0] ?? null;
        
        if ($other === null) {
            return false;
        }
        
        return $value === ($this->data[$other] ?? null);
    }
    
    protected function validateDifferent(string $field, $value, array $parameters): bool
    {
        $other = $parameters[0] ?? null;
        
        if ($other === null) {
            return false;
        }
        
        return $value !== ($this->data[$other] ?? null);
    }
    
    protected function validateIn(string $field, $value, array $parameters): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        return in_array($value, $parameters);
    }
    
    protected function validateNotIn(string $field, $value, array $parameters): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        return !in_array($value, $parameters);
    }
    
    protected function validateRegex(string $field, $value, array $parameters): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        $pattern = $parameters[0] ?? null;
        
        if ($pattern === null) {
            return false;
        }
        
        return preg_match($pattern, $value) === 1;
    }
    
    protected function validateUnique(string $field, $value, array $parameters): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        $table = $parameters[0] ?? null;
        $column = $parameters[1] ?? $field;
        $except = $parameters[2] ?? null;
        
        if ($table === null) {
            return false;
        }
        
        $query = DB::table($table)->where($column, '=', $value);
        
        if ($except !== null) {
            $query->where('id', '!=', $except);
        }
        
        return $query->count() === 0;
    }
    
    protected function validateExists(string $field, $value, array $parameters): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        $table = $parameters[0] ?? null;
        $column = $parameters[1] ?? $field;
        
        if ($table === null) {
            return false;
        }
        
        return DB::table($table)->where($column, '=', $value)->count() > 0;
    }
    
    protected function validateConfirmed(string $field, $value): bool
    {
        $confirmationField = $field . '_confirmation';
        
        return isset($this->data[$confirmationField]) && 
               $value === $this->data[$confirmationField];
    }
}
