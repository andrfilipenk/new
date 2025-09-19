<?php
// app/Core/Validation/Rules/Email.php
namespace Core\Validation\Rules;

class Email implements RuleInterface
{
    public function passes(string $attribute, $value, array $parameters = [], array $data = []): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function message(string $attribute, $value, array $parameters = []): string
    {
        return "The {$attribute} field must be a valid email address.";
    }
}