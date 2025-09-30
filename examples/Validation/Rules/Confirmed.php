<?php
// app/Core/Validation/Rules/Confirmed.php
namespace Core\Validation\Rules;

class Confirmed implements RuleInterface
{
    public function passes(string $attribute, $value, array $parameters = [], array $data = []): bool
    {
        $confirmationField = $attribute . '_confirmation';
        return isset($data[$confirmationField]) && $value === $data[$confirmationField];
    }

    public function message(string $attribute, $value, array $parameters = []): string
    {
        return "The {$attribute} confirmation does not match.";
    }
}