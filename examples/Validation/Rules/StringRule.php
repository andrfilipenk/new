<?php
// app/Core/Validation/Rules/StringRule.php
namespace Core\Validation\Rules;

class StringRule implements RuleInterface
{
    public function passes(string $attribute, $value, array $parameters = [], array $data = []): bool
    {
        if ($value === null) {
            return true;
        }
        return is_string($value);
    }

    public function message(string $attribute, $value, array $parameters = []): string
    {
        return "The {$attribute} field must be a string.";
    }
}