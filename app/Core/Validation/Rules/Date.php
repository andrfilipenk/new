<?php
// app/Core/Validation/Rules/Date.php
namespace Core\Validation\Rules;

class Date implements RuleInterface
{
    public function passes(string $attribute, $value, array $parameters = [], array $data = []): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        // Try to parse the date
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return false;
        }
        // Check if the date is valid by formatting it back
        $date = date('Y-m-d', $timestamp);
        return strtotime($date) !== false;
    }

    public function message(string $attribute, $value, array $parameters = []): string
    {
        return "The {$attribute} field must be a valid date.";
    }
}