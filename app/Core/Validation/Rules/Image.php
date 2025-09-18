<?php
// app/Core/Validation/Rules/Image.php
namespace Core\Validation\Rules;

use Core\Http\UploadedFile;

class Image implements RuleInterface
{
    public function passes(string $attribute, $value, array $parameters = [], array $data = []): bool
    {
        if (is_null($value)) {
            return true; // Use 'required' rule for required validation
        }
        
        // Handle UploadedFile objects
        if ($value instanceof UploadedFile) {
            $mimeType = $value->getMimeType();
            return $this->isImageMimeType($mimeType);
        }
        
        // Handle file paths
        if (is_string($value) && file_exists($value)) {
            $imageInfo = getimagesize($value);
            return $imageInfo !== false;
        }
        
        return false;
    }

    public function message(string $attribute, $value, array $parameters = []): string
    {
        return "The {$attribute} field must be an image.";
    }

    private function isImageMimeType(string $mimeType): bool
    {
        $allowedMimeTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'image/bmp'
        ];
        
        return in_array($mimeType, $allowedMimeTypes);
    }
}