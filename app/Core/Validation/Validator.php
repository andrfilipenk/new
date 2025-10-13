<?php
// app/Core/Validation/Validator.php
namespace Core\Validation;

use Core\Di\Injectable;
use Core\Exception\ValidationException;
use DateTime;

class Validator
{
    use Injectable;

    protected array $errors = [];

    /**
     * Validate input data against rules
     */
    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];
        foreach ($rules as $field => $ruleSet) {
            $value = $data[$field] ?? null;
            foreach ((array) $ruleSet as $rule) {
                [$ruleName, $param] = explode(':', $rule . ':', 2);
                $param = rtrim($param, ':');
                $this->applyRule($field, $value, $ruleName, $param, $data);
            }
        }
        if (!empty($this->errors)) {
            throw new ValidationException($this->errors);
        }
        return true;
    }

    /**
     * Get validation errors
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Apply a single validation rule
     */
    protected function applyRule(string $field, $value, string $rule, string $param, array $data): void
    {
        switch ($rule) {
            case 'required':
                if (is_null($value) || $value === '') {
                    $this->errors[$field][] = "$field is required";
                }
                break;
            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field][] = "$field must be a valid email";
                }
                break;
            case 'max':
                if ($value && strlen($value) > (int) $param) {
                    $this->errors[$field][] = "$field must not exceed $param characters";
                }
                break;
            case 'min':
                if ($value && strlen($value) < (int) $param) {
                    $this->errors[$field][] = "$field must be at least $param characters";
                }
                break;
            case 'numeric':
                if ($value && !is_numeric($value)) {
                    $this->errors[$field][] = "$field must be numeric";
                }
                break;
            case 'date':
                if ($value && !DateTime::createFromFormat($param ?: 'Y-m-d', $value)) {
                    $this->errors[$field][] = "$field must be a valid date" . ($param ? " in $param format" : '');
                }
                break;
            case 'date_range':
                if ($value && $data[$param]) {
                    $start = DateTime::createFromFormat('Y-m-d', $value);
                    $end = DateTime::createFromFormat('Y-m-d', $data[$param]);
                    if (!$start || !$end || $start > $end) {
                        $this->errors[$field][] = "$field must be before or equal to $param";
                    }
                }
                break;
            case 'in':
                $options = explode(',', $param);
                if ($value && !in_array($value, $options)) {
                    $this->errors[$field][] = "$field must be one of: $param";
                }
                break;
        }
    }
}

/*

$validator = $this->getDI()->get('validator');
if ($validator->validate($data, [
    'name' => ['required', 'max:255'],
    'email' => ['required', 'email', 'unique:users'],
    'custom_id' => ['required', 'numeric', 'unique:users'],
    'password' => ['required', 'min:8'],
    
    'start_date' => ['required', 'date:Y-m-d'],
    'end_date' => ['required', 'date:Y-m-d', 'date_range:start_date']
])) {
    if ((new UserModel($data))->save()) {
        $this->flashSuccess('User created.');
        return $this->redirect('admin/user');
    }
    $this->flashError('Failed to create user.');
}
*/