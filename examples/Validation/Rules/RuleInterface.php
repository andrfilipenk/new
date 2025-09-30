<?php
// app/Core/Validation/Rules/RuleInterface.php
namespace Core\Validation\Rules;

interface RuleInterface
{
    /**
     * Determine if the validation rule passes
     */
    public function passes(string $attribute, $value, array $parameters = [], array $data = []): bool;

    /**
     * Get the validation error message
     */
    public function message(string $attribute, $value, array $parameters = []): string;
}