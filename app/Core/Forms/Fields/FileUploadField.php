<?php
namespace Core\Forms\Fields;

use Core\Forms\Validation\ValidationResult;

class FileUploadField extends AbstractField
{
    /**
     * @var array Allowed MIME types
     */
    private array $allowedMimeTypes = [];

    /**
     * @var array Allowed file extensions
     */
    private array $allowedExtensions = [];

    /**
     * @var int Maximum file size in bytes
     */
    private int $maxFileSize = 10485760; // 10MB default

    /**
     * @var int Minimum file size in bytes
     */
    private int $minFileSize = 0;

    /**
     * @var bool Allow multiple file uploads
     */
    private bool $multiple = false;

    /**
     * @var int Maximum number of files (when multiple is true)
     */
    private int $maxFiles = 5;

    /**
     * @var string Upload directory path
     */
    private string $uploadDirectory = '';

    /**
     * @var bool Whether to validate image dimensions
     */
    private bool $validateImageDimensions = false;

    /**
     * @var int|null Maximum image width
     */
    private ?int $maxWidth = null;

    /**
     * @var int|null Maximum image height
     */
    private ?int $maxHeight = null;

    /**
     * @var int|null Minimum image width
     */
    private ?int $minWidth = null;

    /**
     * @var int|null Minimum image height
     */
    private ?int $minHeight = null;

    /**
     * Common MIME type presets
     */
    private const MIME_PRESETS = [
        'images' => [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 
            'image/webp', 'image/svg+xml'
        ],
        'documents' => [
            'application/pdf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain', 'text/csv'
        ],
        'archives' => [
            'application/zip', 'application/x-rar-compressed',
            'application/x-7z-compressed', 'application/x-tar'
        ],
        'videos' => [
            'video/mp4', 'video/mpeg', 'video/quicktime',
            'video/x-msvideo', 'video/webm'
        ],
        'audio' => [
            'audio/mpeg', 'audio/wav', 'audio/ogg', 
            'audio/webm', 'audio/mp4'
        ]
    ];

    /**
     * {@inheritdoc}
     */
    protected static function getDefaultType(): string
    {
        return 'file';
    }

    /**
     * Set allowed MIME types
     * 
     * @param array $mimeTypes Array of MIME types or preset names
     * @return self
     */
    public function setAllowedMimeTypes(array $mimeTypes): self
    {
        $this->allowedMimeTypes = [];
        
        foreach ($mimeTypes as $type) {
            if (isset(self::MIME_PRESETS[$type])) {
                $this->allowedMimeTypes = array_merge(
                    $this->allowedMimeTypes, 
                    self::MIME_PRESETS[$type]
                );
            } else {
                $this->allowedMimeTypes[] = $type;
            }
        }
        
        $this->allowedMimeTypes = array_unique($this->allowedMimeTypes);
        return $this;
    }

    /**
     * Get allowed MIME types
     * 
     * @return array
     */
    public function getAllowedMimeTypes(): array
    {
        return $this->allowedMimeTypes;
    }

    /**
     * Set allowed file extensions
     * 
     * @param array $extensions Array of file extensions (without dots)
     * @return self
     */
    public function setAllowedExtensions(array $extensions): self
    {
        $this->allowedExtensions = array_map(
            fn($ext) => ltrim(strtolower($ext), '.'),
            $extensions
        );
        return $this;
    }

    /**
     * Get allowed file extensions
     * 
     * @return array
     */
    public function getAllowedExtensions(): array
    {
        return $this->allowedExtensions;
    }

    /**
     * Set maximum file size
     * 
     * @param int $bytes Maximum file size in bytes
     * @return self
     */
    public function setMaxFileSize(int $bytes): self
    {
        $this->maxFileSize = $bytes;
        return $this;
    }

    /**
     * Get maximum file size
     * 
     * @return int
     */
    public function getMaxFileSize(): int
    {
        return $this->maxFileSize;
    }

    /**
     * Set minimum file size
     * 
     * @param int $bytes Minimum file size in bytes
     * @return self
     */
    public function setMinFileSize(int $bytes): self
    {
        $this->minFileSize = $bytes;
        return $this;
    }

    /**
     * Enable multiple file uploads
     * 
     * @param bool $multiple Whether to allow multiple files
     * @param int $maxFiles Maximum number of files
     * @return self
     */
    public function setMultiple(bool $multiple = true, int $maxFiles = 5): self
    {
        $this->multiple = $multiple;
        $this->maxFiles = $maxFiles;
        return $this;
    }

    /**
     * Check if multiple uploads are allowed
     * 
     * @return bool
     */
    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    /**
     * Set upload directory
     * 
     * @param string $directory Upload directory path
     * @return self
     */
    public function setUploadDirectory(string $directory): self
    {
        $this->uploadDirectory = rtrim($directory, '/\\');
        return $this;
    }

    /**
     * Set image dimension constraints
     * 
     * @param array $constraints Associative array with maxWidth, maxHeight, minWidth, minHeight
     * @return self
     */
    public function setImageDimensions(array $constraints): self
    {
        $this->validateImageDimensions = true;
        
        if (isset($constraints['maxWidth'])) {
            $this->maxWidth = (int) $constraints['maxWidth'];
        }
        if (isset($constraints['maxHeight'])) {
            $this->maxHeight = (int) $constraints['maxHeight'];
        }
        if (isset($constraints['minWidth'])) {
            $this->minWidth = (int) $constraints['minWidth'];
        }
        if (isset($constraints['minHeight'])) {
            $this->minHeight = (int) $constraints['minHeight'];
        }
        
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function render(array $context = []): string
    {
        $theme = $context['theme'] ?? null;
        
        if ($theme && method_exists($theme, 'renderField')) {
            return $theme->renderField($this, $context, []);
        }
        
        return $this->renderDefault($context);
    }

    /**
     * Render default file upload field
     * 
     * @param array $context Rendering context
     * @return string HTML output
     */
    protected function renderDefault(array $context): string
    {
        $attributes = $this->getAttributes();
        $attributes['type'] = 'file';
        
        if ($this->multiple) {
            $attributes['multiple'] = 'multiple';
            $attributes['name'] = $this->name . '[]';
        }
        
        // Add accept attribute for MIME types
        if (!empty($this->allowedMimeTypes)) {
            $acceptList = $this->allowedMimeTypes;
            
            // Add extensions to accept list
            if (!empty($this->allowedExtensions)) {
                foreach ($this->allowedExtensions as $ext) {
                    $acceptList[] = '.' . $ext;
                }
            }
            
            $attributes['accept'] = implode(',', $acceptList);
        }
        
        $attributesString = $this->buildAttributesString($attributes);
        
        return sprintf('<input %s />', $attributesString);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(mixed $value = null): ValidationResult
    {
        $value = $value ?? $this->value;
        
        // Check if file was uploaded
        if ($this->required && empty($value)) {
            return ValidationResult::failure([
                'required' => 'File upload is required'
            ]);
        }
        
        if (empty($value)) {
            return ValidationResult::success();
        }
        
        // Handle multiple files
        if ($this->multiple && is_array($value)) {
            return $this->validateMultipleFiles($value);
        }
        
        // Validate single file
        return $this->validateSingleFile($value);
    }

    /**
     * Validate multiple uploaded files
     * 
     * @param array $files Array of file data
     * @return ValidationResult
     */
    private function validateMultipleFiles(array $files): ValidationResult
    {
        $errors = [];
        
        // Check maximum file count
        if (count($files) > $this->maxFiles) {
            return ValidationResult::failure([
                'max_files' => sprintf(
                    'Maximum %d files allowed, %d provided',
                    $this->maxFiles,
                    count($files)
                )
            ]);
        }
        
        // Validate each file
        foreach ($files as $index => $file) {
            $result = $this->validateSingleFile($file);
            
            if (!$result->isValid()) {
                foreach ($result->getErrors() as $key => $message) {
                    $errors["file_{$index}_{$key}"] = "File " . ($index + 1) . ": {$message}";
                }
            }
        }
        
        return empty($errors) 
            ? ValidationResult::success() 
            : ValidationResult::failure($errors);
    }

    /**
     * Validate a single uploaded file
     * 
     * @param mixed $file File data (array or object)
     * @return ValidationResult
     */
    private function validateSingleFile(mixed $file): ValidationResult
    {
        $errors = [];
        
        // Handle different file input formats
        if (is_array($file)) {
            $fileName = $file['name'] ?? '';
            $fileSize = $file['size'] ?? 0;
            $fileTmpName = $file['tmp_name'] ?? '';
            $fileError = $file['error'] ?? UPLOAD_ERR_NO_FILE;
            $fileMimeType = $file['type'] ?? '';
        } else {
            // Unsupported file format
            return ValidationResult::failure([
                'invalid_format' => 'Invalid file data format'
            ]);
        }
        
        // Check for upload errors
        if ($fileError !== UPLOAD_ERR_OK) {
            return ValidationResult::failure([
                'upload_error' => $this->getUploadErrorMessage($fileError)
            ]);
        }
        
        // Validate file size
        if ($fileSize < $this->minFileSize) {
            $errors['min_size'] = sprintf(
                'File size must be at least %s',
                $this->formatBytes($this->minFileSize)
            );
        }
        
        if ($fileSize > $this->maxFileSize) {
            $errors['max_size'] = sprintf(
                'File size must not exceed %s',
                $this->formatBytes($this->maxFileSize)
            );
        }
        
        // Validate MIME type
        if (!empty($this->allowedMimeTypes)) {
            $actualMimeType = $this->detectMimeType($fileTmpName, $fileMimeType);
            
            if (!in_array($actualMimeType, $this->allowedMimeTypes, true)) {
                $errors['mime_type'] = sprintf(
                    'File type %s is not allowed',
                    $actualMimeType
                );
            }
        }
        
        // Validate file extension
        if (!empty($this->allowedExtensions)) {
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            if (!in_array($extension, $this->allowedExtensions, true)) {
                $errors['extension'] = sprintf(
                    'File extension .%s is not allowed',
                    $extension
                );
            }
        }
        
        // Validate image dimensions
        if ($this->validateImageDimensions && !empty($fileTmpName)) {
            $dimensionErrors = $this->validateImageDimensionsInternal($fileTmpName);
            $errors = array_merge($errors, $dimensionErrors);
        }
        
        return empty($errors) 
            ? ValidationResult::success() 
            : ValidationResult::failure($errors);
    }

    /**
     * Validate image dimensions
     * 
     * @param string $filePath Path to uploaded file
     * @return array Validation errors
     */
    private function validateImageDimensionsInternal(string $filePath): array
    {
        $errors = [];
        
        $imageInfo = @getimagesize($filePath);
        
        if ($imageInfo === false) {
            return ['image' => 'File is not a valid image'];
        }
        
        [$width, $height] = $imageInfo;
        
        if ($this->maxWidth && $width > $this->maxWidth) {
            $errors['max_width'] = sprintf(
                'Image width must not exceed %dpx (current: %dpx)',
                $this->maxWidth,
                $width
            );
        }
        
        if ($this->maxHeight && $height > $this->maxHeight) {
            $errors['max_height'] = sprintf(
                'Image height must not exceed %dpx (current: %dpx)',
                $this->maxHeight,
                $height
            );
        }
        
        if ($this->minWidth && $width < $this->minWidth) {
            $errors['min_width'] = sprintf(
                'Image width must be at least %dpx (current: %dpx)',
                $this->minWidth,
                $width
            );
        }
        
        if ($this->minHeight && $height < $this->minHeight) {
            $errors['min_height'] = sprintf(
                'Image height must be at least %dpx (current: %dpx)',
                $this->minHeight,
                $height
            );
        }
        
        return $errors;
    }

    /**
     * Detect actual MIME type of file
     * 
     * @param string $filePath Temporary file path
     * @param string $clientMimeType Client-provided MIME type
     * @return string Detected MIME type
     */
    private function detectMimeType(string $filePath, string $clientMimeType): string
    {
        // Prefer server-side detection
        if (function_exists('mime_content_type') && file_exists($filePath)) {
            $detectedType = mime_content_type($filePath);
            if ($detectedType !== false) {
                return $detectedType;
            }
        }
        
        if (function_exists('finfo_file') && file_exists($filePath)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedType = finfo_file($finfo, $filePath);
            finfo_close($finfo);
            
            if ($detectedType !== false) {
                return $detectedType;
            }
        }
        
        // Fallback to client-provided type
        return $clientMimeType;
    }

    /**
     * Get upload error message
     * 
     * @param int $errorCode PHP upload error code
     * @return string Error message
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form upload limit',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary upload directory',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by PHP extension',
            default => 'Unknown upload error occurred'
        };
    }

    /**
     * Format bytes to human-readable size
     * 
     * @param int $bytes Bytes
     * @return string Formatted size
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);
        
        return sprintf(
            '%.2f %s',
            $bytes / pow(1024, $power),
            $units[$power]
        );
    }

    /**
     * Static factory for image uploads
     * 
     * @param string $name Field name
     * @param array $config Configuration
     * @return self
     */
    public static function image(string $name, array $config = []): self
    {
        $config['type'] = 'file';
        $field = new self($name, $config);
        $field->setAllowedMimeTypes(['images']);
        
        return $field;
    }

    /**
     * Static factory for document uploads
     * 
     * @param string $name Field name
     * @param array $config Configuration
     * @return self
     */
    public static function document(string $name, array $config = []): self
    {
        $config['type'] = 'file';
        $field = new self($name, $config);
        $field->setAllowedMimeTypes(['documents']);
        
        return $field;
    }

    /**
     * Static factory for general file uploads
     * 
     * @param string $name Field name
     * @param array $config Configuration
     * @return self
     */
    public static function any(string $name, array $config = []): self
    {
        $config['type'] = 'file';
        return new self($name, $config);
    }
}
