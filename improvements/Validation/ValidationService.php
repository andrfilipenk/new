<?php
// app/_Core/Validation/ValidationService.php
namespace Core\Validation;

use Core\Di\Injectable;

class ValidationService
{
    use Injectable;
    
    protected $errors = [];
    protected $rules = [];
    
    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];
        $this->rules = $rules;
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $this->validateField($field, $value, $fieldRules);
        }
        
        return empty($this->errors);
    }
    
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    public function getFirstError(string $field = null): ?string
    {
        if ($field) {
            return $this->errors[$field][0] ?? null;
        }
        
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0] ?? null;
        }
        
        return null;
    }
    
    protected function validateField(string $field, $value, array $rules): void
    {
        foreach ($rules as $rule) {
            if (is_string($rule)) {
                $this->applyRule($field, $value, $rule);
            } elseif (is_array($rule)) {
                $this->applyRule($field, $value, $rule[0], array_slice($rule, 1));
            }
        }
    }
    
    protected function applyRule(string $field, $value, string $rule, array $params = []): void
    {
        switch ($rule) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    $this->addError($field, "{$field} is required");
                }
                break;
                
            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "{$field} must be a valid email");
                }
                break;
                
            case 'min_length':
                $min = $params[0] ?? 0;
                if ($value && strlen($value) < $min) {
                    $this->addError($field, "{$field} must be at least {$min} characters");
                }
                break;
                
            case 'max_length':
                $max = $params[0] ?? 255;
                if ($value && strlen($value) > $max) {
                    $this->addError($field, "{$field} must not exceed {$max} characters");
                }
                break;
                
            case 'unique':
                $table = $params[0] ?? null;
                $column = $params[1] ?? $field;
                $except = $params[2] ?? null;
                
                if ($value && $table) {
                    $query = $this->getDI()->get('db')->table($table)
                        ->where($column, $value);
                    
                    if ($except) {
                        $query->where('id', '!=', $except);
                    }
                    
                    if ($query->count() > 0) {
                        $this->addError($field, "{$field} already exists");
                    }
                }
                break;
                
            case 'exists':
                $table = $params[0] ?? null;
                $column = $params[1] ?? $field;
                
                if ($value && $table) {
                    $count = $this->getDI()->get('db')->table($table)
                        ->where($column, $value)
                        ->count();
                    
                    if ($count === 0) {
                        $this->addError($field, "{$field} does not exist");
                    }
                }
                break;
        }
    }
    
    protected function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }
}