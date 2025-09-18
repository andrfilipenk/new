<?php
// app/Core/Validation/Rules/Before.php
namespace Core\Validation\Rules;

class Before implements RuleInterface
{
    public function passes(string $attribute, $value, array $parameters = [], array $data = []): bool
    {
        if (is_null($value) || $value === '') {
            return true; // Use 'required' rule for required validation
        }
        
        if (empty($parameters[0])) {
            throw new \InvalidArgumentException('Before rule requires a date parameter');
        }
        
        $beforeDate = $parameters[0];
        
        // Convert values to timestamps for comparison
        $valueTimestamp = strtotime($value);
        $beforeTimestamp = strtotime($beforeDate);
        
        if ($valueTimestamp === false || $beforeTimestamp === false) {
            return false;
        }
        
        return $valueTimestamp < $beforeTimestamp;
    }

    public function message(string $attribute, $value, array $parameters = []): string
    {
        $beforeDate = $parameters[0] ?? 'specified date';
        return "The {$attribute} field must be before {$beforeDate}.";
    }
}