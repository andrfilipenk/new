<?php
// app/Core/Validation/Rules/Numeric.php
namespace Core\Validation\Rules;

class Numeric implements RuleInterface
{
    public function passes(string $attribute, $value, array $parameters = [], array $data = []): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        return is_numeric($value);
    }

    public function message(string $attribute, $value, array $parameters = []): string
    {
        return "The {$attribute} field must be numeric.";
    }
}