<?php
// app/Core/Validation/Validator.php
namespace Core\Validation;

use Core\Validation\Rules\RuleInterface;

/**
 * Enterprise-level validation system with chainable rules
 * Following super-senior PHP practices with interface segregation
 */
class Validator
{
    protected array $data;
    protected array $rules;
    protected array $errors = [];
    protected array $messages = [];
    
    // Built-in validation rules
    protected array $ruleMap = [
        'required'      => Rules\Required::class,
        'email'         => Rules\Email::class,
        'min'           => Rules\Min::class,
        'max'           => Rules\Max::class,
        'numeric'       => Rules\Numeric::class,
        'integer'       => Rules\Integer::class,
        'string'        => Rules\StringRule::class,
        'confirmed'     => Rules\Confirmed::class,
        'unique'        => Rules\Unique::class,
        'exists'        => Rules\Exists::class,
        'regex'         => Rules\Regex::class,
        'in'            => Rules\In::class,
        'date'          => Rules\Date::class,
        'before'        => Rules\Before::class,
        'after'         => Rules\After::class,
        'image'         => Rules\Image::class,
        'file'          => Rules\File::class,
    ];

    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data     = $data;
        $this->rules    = $rules;
        $this->messages = $messages;
    }

    public function passes(): bool
    {
        $this->errors = [];
        foreach ($this->rules as $field => $fieldRules) {
            $value = $this->data[$field] ?? null;
            $rules = $this->parseRules($fieldRules);
            foreach ($rules as $rule) {
                if (!$this->validateRule($field, $value, $rule)) {
                    break; // Stop on first failure for this field
                }
            }
        }
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(string $field = null): ?string
    {
        if ($field) {
            return $this->errors[$field][0] ?? null;
        }
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0] ?? null;
        }
        return null;
    }

    protected function validateRule(string $field, $value, array $rule): bool
    {
        $ruleName   = $rule['name'];
        $parameters = $rule['parameters'];
        if (!isset($this->ruleMap[$ruleName])) {
            throw new ValidationException("Unknown validation rule: {$ruleName}");
        }
        $ruleClass      = $this->ruleMap[$ruleName];
        $ruleInstance   = new $ruleClass();
        if (!$ruleInstance instanceof RuleInterface) {
            throw new ValidationException("Rule {$ruleName} must implement RuleInterface");
        }
        $passes = $ruleInstance->passes($field, $value, $parameters, $this->data);
        if (!$passes) {
            $this->addError($field, $ruleInstance->message($field, $value, $parameters));
        }
        return $passes;
    }

    protected function parseRules($rules): array
    {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }
        $parsed = [];
        foreach ($rules as $rule) {
            if (is_string($rule)) {
                $parsed[] = $this->parseStringRule($rule);
            } elseif ($rule instanceof RuleInterface) {
                $parsed[] = ['name' => get_class($rule), 'parameters' => [], 'instance' => $rule];
            }
        }
        return $parsed;
    }

    protected function parseStringRule(string $rule): array
    {
        if (strpos($rule, ':') !== false) {
            [$name, $parameters] = explode(':', $rule, 2);
            $parameters = explode(',', $parameters);
        } else {
            $name = $rule;
            $parameters = [];
        }
        return ['name' => $name, 'parameters' => $parameters];
    }

    protected function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    public function addCustomRule(string $name, string $class): void
    {
        $this->ruleMap[$name] = $class;
    }

    /**
     * Static factory method for quick validation
     */
    public static function make(array $data, array $rules, array $messages = []): static
    {
        return new static($data, $rules, $messages);
    }

    /**
     * Validate data and return it or throw exception
     */
    public static function validate(array $data, array $rules, array $messages = []): array
    {
        $validator = static::make($data, $rules, $messages);
        if ($validator->fails()) {
            throw new ValidationException('Validation failed', $validator->errors());
        }
        return $data;
    }
}