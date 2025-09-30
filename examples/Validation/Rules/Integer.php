<?php
// app/Core/Validation/Rules/Integer.php
namespace Core\Validation\Rules;

class Integer implements RuleInterface
{
    public function passes(string $attribute, $value, array $parameters = [], array $data = []): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    public function message(string $attribute, $value, array $parameters = []): string
    {
        return "The {$attribute} field must be an integer.";
    }
}