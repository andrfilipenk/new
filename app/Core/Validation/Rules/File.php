<?php
// app/Core/Validation/Rules/File.php
namespace Core\Validation\Rules;

use Core\Http\UploadedFile;

class File implements RuleInterface
{
    public function passes(string $attribute, $value, array $parameters = [], array $data = []): bool
    {
        if (is_null($value)) {
            return true; // Use 'required' rule for required validation
        }
        
        // Handle UploadedFile objects
        if ($value instanceof UploadedFile) {
            return $value->isValid();
        }
        
        // Handle file paths
        if (is_string($value)) {
            return file_exists($value) && is_file($value);
        }
        
        return false;
    }

    public function message(string $attribute, $value, array $parameters = []): string
    {
        return "The {$attribute} field must be a file.";
    }
}