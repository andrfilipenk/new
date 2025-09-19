<?php
// app/Core/Validation/Rules/Required.php
namespace Core\Validation\Rules;

class Required implements RuleInterface
{
    public function passes(string $attribute, $value, array $parameters = [], array $data = []): bool
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

    public function message(string $attribute, $value, array $parameters = []): string
    {
        return "The {$attribute} field is required.";
    }
}