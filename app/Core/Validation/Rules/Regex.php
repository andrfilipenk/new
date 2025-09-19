<?php
// app/Core/Validation/Rules/Regex.php
namespace Core\Validation\Rules;

class Regex implements RuleInterface
{
    public function passes(string $attribute, $value, array $parameters = [], array $data = []): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (empty($parameters[0])) {
            throw new \InvalidArgumentException('Regex rule requires a pattern parameter');
        }
        $pattern = $parameters[0];
        return preg_match($pattern, $value) === 1;
    }

    public function message(string $attribute, $value, array $parameters = []): string
    {
        return "The {$attribute} field format is invalid.";
    }
}