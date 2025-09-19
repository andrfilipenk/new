<?php
// Test the UploadedFile getMimeType() fix
require_once __DIR__ . '/../app/bootstrap.php';

use Core\Http\UploadedFile;
use Core\Validation\Rules\Image;

// Simulate a file upload array (like $_FILES)
$mockFileData = [
    'name' => 'test-image.jpg',
    'type' => 'image/jpeg',
    'tmp_name' => '/tmp/phpABC123', // This would be a real temp file in practice
    'error' => UPLOAD_ERR_OK,
    'size' => 12345
];

// Create UploadedFile instance
$uploadedFile = new UploadedFile($mockFileData);

// Test the methods
echo "Testing UploadedFile methods:\n";
echo "Original name: " . $uploadedFile->getClientOriginalName() . "\n";
echo "Client MIME type: " . $uploadedFile->getClientMimeType() . "\n";
echo "getMimeType() (new method): " . $uploadedFile->getMimeType() . "\n";
echo "Size: " . $uploadedFile->getSize() . " bytes\n";
echo "Is valid: " . ($uploadedFile->isValid() ? 'Yes' : 'No') . "\n";

// Test Image validation rule
$imageRule = new Image();
echo "\nTesting Image validation rule:\n";

// This would work now since getMimeType() exists
// Note: In real usage, the file would need to exist for server-side validation
echo "Image rule message: " . $imageRule->message('photo', $uploadedFile) . "\n";

echo "\nFix successful! The getMimeType() method now exists on UploadedFile.\n";