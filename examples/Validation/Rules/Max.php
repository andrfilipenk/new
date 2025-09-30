<?php
// app/Core/Validation/Rules/Max.php
namespace Core\Validation\Rules;

class Max implements RuleInterface
{
    public function passes(string $attribute, $value, array $parameters = [], array $data = []): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        $max = $parameters[0] ?? 0;
        if (is_numeric($value)) {
            return $value <= $max;
        }
        if (is_string($value)) {
            return strlen($value) <= $max;
        }
        if (is_array($value)) {
            return count($value) <= $max;
        }
        return false;
    }

    public function message(string $attribute, $value, array $parameters = []): string
    {
        $max = $parameters[0] ?? 0;
        if (is_numeric($value)) {
            return "The {$attribute} field must not be greater than {$max}.";
        }
        return "The {$attribute} field must not be greater than {$max} characters.";
    }
}