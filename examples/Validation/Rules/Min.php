<?php
// app/Core/Validation/Rules/Min.php
namespace Core\Validation\Rules;

class Min implements RuleInterface
{
    public function passes(string $attribute, $value, array $parameters = [], array $data = []): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        $min = $parameters[0] ?? 0;
        if (is_numeric($value)) {
            return $value >= $min;
        }
        if (is_string($value)) {
            return strlen($value) >= $min;
        }
        if (is_array($value)) {
            return count($value) >= $min;
        }
        return false;
    }

    public function message(string $attribute, $value, array $parameters = []): string
    {
        $min = $parameters[0] ?? 0;
        if (is_numeric($value)) {
            return "The {$attribute} field must be at least {$min}.";
        }
        return "The {$attribute} field must be at least {$min} characters.";
    }
}