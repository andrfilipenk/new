<?php
// app/Core/Validation/Rules/In.php
namespace Core\Validation\Rules;

/**
 * In validation rule
 * Validates that a field's value is one of the given list of values
 */
class In implements RuleInterface
{
    public function passes(string $attribute, $value, array $parameters = [], array $data = []): bool
    {
        if ($value === null || $value === '') {
            return true; // Let required rule handle empty values
        }
        
        if (empty($parameters)) {
            return false; // No valid options provided
        }
        
        // Convert value to string for comparison to handle type differences
        $stringValue = (string) $value;
        
        foreach ($parameters as $option) {
            if ($stringValue === (string) $option) {
                return true;
            }
        }
        
        return false;
    }
    
    public function message(string $attribute, $value, array $parameters = []): string
    {
        $options = implode(', ', $parameters);
        return "The {$attribute} field must be one of the following: {$options}.";
    }
}