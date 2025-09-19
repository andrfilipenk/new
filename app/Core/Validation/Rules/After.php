<?php
// app/Core/Validation/Rules/After.php
namespace Core\Validation\Rules;

class After implements RuleInterface
{
    public function passes(string $attribute, $value, array $parameters = [], array $data = []): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (empty($parameters[0])) {
            throw new \InvalidArgumentException('After rule requires a date parameter');
        }
        $afterDate = $parameters[0];
        // Convert values to timestamps for comparison
        $valueTimestamp = strtotime($value);
        $afterTimestamp = strtotime($afterDate);
        if ($valueTimestamp === false || $afterTimestamp === false) {
            return false;
        }
        return $valueTimestamp > $afterTimestamp;
    }

    public function message(string $attribute, $value, array $parameters = []): string
    {
        $afterDate = $parameters[0] ?? 'specified date';
        return "The {$attribute} field must be after {$afterDate}.";
    }
}