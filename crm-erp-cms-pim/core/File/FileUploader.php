<?php

namespace Core\File;

use Core\Validation\Validator;

class FileUploader
{
    private array $config;
    private array $errors = [];
    
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'upload_path' => $_SERVER['DOCUMENT_ROOT'] . '/storage/uploads',
            'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx'],
            'max_size' => 10485760, // 10MB in bytes
            'max_width' => null,
            'max_height' => null,
            'min_width' => null,
            'min_height' => null,
            'encrypt_name' => true,
            'overwrite' => false,
            'create_thumb' => false,
            'thumb_width' => 150,
            'thumb_height' => 150,
            'maintain_ratio' => true
        ], $config);
        
        // Create upload directory if it doesn't exist
        if (!is_dir($this->config['upload_path'])) {
            mkdir($this->config['upload_path'], 0755, true);
        }
    }
    
    /**
     * Upload a single file
     */
    public function upload(array $file, ?string $customName = null): array|false
    {
        $this->errors = [];
        
        // Validate file
        if (!$this->validateFile($file)) {
            return false;
        }
        
        // Generate filename
        $filename = $this->generateFilename($file['name'], $customName);
        $filepath = $this->config['upload_path'] . '/' . $filename;
        
        // Check if file exists and overwrite is disabled
        if (file_exists($filepath) && !$this->config['overwrite']) {
            $filename = $this->generateUniqueFilename($filename);
            $filepath = $this->config['upload_path'] . '/' . $filename;
        }
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            $this->errors[] = 'Failed to move uploaded file';
            return false;
        }
        
        // Set file permissions
        chmod($filepath, 0644);
        
        // Create thumbnail if configured
        $thumbnailPath = null;
        if ($this->config['create_thumb'] && $this->isImage($filepath)) {
            $thumbnailPath = $this->createThumbnail($filepath);
        }
        
        return [
            'filename' => $filename,
            'filepath' => $filepath,
            'relative_path' => '/storage/uploads/' . $filename,
            'size' => filesize($filepath),
            'mime_type' => mime_content_type($filepath),
            'extension' => pathinfo($filename, PATHINFO_EXTENSION),
            'thumbnail' => $thumbnailPath,
            'uploaded_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Upload multiple files
     */
    public function uploadMultiple(array $files): array
    {
        $results = [];
        
        // Normalize files array
        $normalizedFiles = $this->normalizeFilesArray($files);
        
        foreach ($normalizedFiles as $file) {
            $result = $this->upload($file);
            if ($result) {
                $results[] = $result;
            }
        }
        
        return $results;
    }
    
    /**
     * Validate uploaded file
     */
    private function validateFile(array $file): bool
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = $this->getUploadErrorMessage($file['error']);
            return false;
        }
        
        // Check file size
        if ($file['size'] > $this->config['max_size']) {
            $this->errors[] = 'File size exceeds maximum allowed size of ' . 
                              $this->formatBytes($this->config['max_size']);
            return false;
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->config['allowed_types'])) {
            $this->errors[] = 'File type not allowed. Allowed types: ' . 
                              implode(', ', $this->config['allowed_types']);
            return false;
        }
        
        // Validate image dimensions if applicable
        if ($this->isImage($file['tmp_name'])) {
            if (!$this->validateImageDimensions($file['tmp_name'])) {
                return false;
            }
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = $this->getAllowedMimeTypes();
        if (!in_array($mimeType, $allowedMimes)) {
            $this->errors[] = 'Invalid file MIME type';
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate image dimensions
     */
    private function validateImageDimensions(string $filepath): bool
    {
        $imageInfo = getimagesize($filepath);
        if (!$imageInfo) {
            return true; // Not an image or can't read dimensions
        }
        
        [$width, $height] = $imageInfo;
        
        if ($this->config['max_width'] && $width > $this->config['max_width']) {
            $this->errors[] = "Image width exceeds maximum of {$this->config['max_width']}px";
            return false;
        }
        
        if ($this->config['max_height'] && $height > $this->config['max_height']) {
            $this->errors[] = "Image height exceeds maximum of {$this->config['max_height']}px";
            return false;
        }
        
        if ($this->config['min_width'] && $width < $this->config['min_width']) {
            $this->errors[] = "Image width is less than minimum of {$this->config['min_width']}px";
            return false;
        }
        
        if ($this->config['min_height'] && $height < $this->config['min_height']) {
            $this->errors[] = "Image height is less than minimum of {$this->config['min_height']}px";
            return false;
        }
        
        return true;
    }
    
    /**
     * Generate filename
     */
    private function generateFilename(string $originalName, ?string $customName = null): string
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        if ($customName) {
            return $customName . '.' . $extension;
        }
        
        if ($this->config['encrypt_name']) {
            return md5(uniqid(mt_rand(), true)) . '.' . $extension;
        }
        
        // Sanitize original filename
        $filename = pathinfo($originalName, PATHINFO_FILENAME);
        $filename = preg_replace('/[^a-zA-Z0-9-_]/', '_', $filename);
        return $filename . '.' . $extension;
    }
    
    /**
     * Generate unique filename if file exists
     */
    private function generateUniqueFilename(string $filename): string
    {
        $pathInfo = pathinfo($filename);
        $name = $pathInfo['filename'];
        $extension = $pathInfo['extension'];
        $counter = 1;
        
        while (file_exists($this->config['upload_path'] . '/' . $filename)) {
            $filename = $name . '_' . $counter . '.' . $extension;
            $counter++;
        }
        
        return $filename;
    }
    
    /**
     * Create thumbnail for image
     */
    private function createThumbnail(string $sourcePath): ?string
    {
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return null;
        }
        
        [$srcWidth, $srcHeight, $imageType] = $imageInfo;
        
        // Create source image
        $srcImage = match($imageType) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => imagecreatefrompng($sourcePath),
            IMAGETYPE_GIF => imagecreatefromgif($sourcePath),
            default => null
        };
        
        if (!$srcImage) {
            return null;
        }
        
        // Calculate thumbnail dimensions
        if ($this->config['maintain_ratio']) {
            $ratio = min(
                $this->config['thumb_width'] / $srcWidth,
                $this->config['thumb_height'] / $srcHeight
            );
            $thumbWidth = (int)($srcWidth * $ratio);
            $thumbHeight = (int)($srcHeight * $ratio);
        } else {
            $thumbWidth = $this->config['thumb_width'];
            $thumbHeight = $this->config['thumb_height'];
        }
        
        // Create thumbnail
        $thumbImage = imagecreatetruecolor($thumbWidth, $thumbHeight);
        
        // Preserve transparency for PNG and GIF
        if ($imageType === IMAGETYPE_PNG || $imageType === IMAGETYPE_GIF) {
            imagealphablending($thumbImage, false);
            imagesavealpha($thumbImage, true);
            $transparent = imagecolorallocatealpha($thumbImage, 255, 255, 255, 127);
            imagefilledrectangle($thumbImage, 0, 0, $thumbWidth, $thumbHeight, $transparent);
        }
        
        imagecopyresampled(
            $thumbImage, $srcImage,
            0, 0, 0, 0,
            $thumbWidth, $thumbHeight,
            $srcWidth, $srcHeight
        );
        
        // Save thumbnail
        $pathInfo = pathinfo($sourcePath);
        $thumbFilename = $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];
        $thumbPath = $this->config['upload_path'] . '/' . $thumbFilename;
        
        $saved = match($imageType) {
            IMAGETYPE_JPEG => imagejpeg($thumbImage, $thumbPath, 85),
            IMAGETYPE_PNG => imagepng($thumbImage, $thumbPath, 8),
            IMAGETYPE_GIF => imagegif($thumbImage, $thumbPath),
            default => false
        };
        
        imagedestroy($srcImage);
        imagedestroy($thumbImage);
        
        return $saved ? $thumbFilename : null;
    }
    
    /**
     * Check if file is an image
     */
    private function isImage(string $filepath): bool
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        return in_array($extension, $imageExtensions);
    }
    
    /**
     * Get allowed MIME types based on allowed extensions
     */
    private function getAllowedMimeTypes(): array
    {
        $mimeMap = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'zip' => 'application/zip',
            'txt' => 'text/plain',
            'csv' => 'text/csv'
        ];
        
        $allowedMimes = [];
        foreach ($this->config['allowed_types'] as $ext) {
            if (isset($mimeMap[$ext])) {
                $allowedMimes[] = $mimeMap[$ext];
            }
        }
        
        return $allowedMimes;
    }
    
    /**
     * Normalize files array for multiple uploads
     */
    private function normalizeFilesArray(array $files): array
    {
        $normalized = [];
        
        if (isset($files['name']) && is_array($files['name'])) {
            // Multiple files in HTML array format
            foreach ($files['name'] as $key => $name) {
                $normalized[] = [
                    'name' => $files['name'][$key],
                    'type' => $files['type'][$key],
                    'tmp_name' => $files['tmp_name'][$key],
                    'error' => $files['error'][$key],
                    'size' => $files['size'][$key]
                ];
            }
        } else {
            // Single file or already normalized
            $normalized[] = $files;
        }
        
        return $normalized;
    }
    
    /**
     * Get upload error message
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        return match($errorCode) {
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive in HTML form',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
            default => 'Unknown upload error'
        };
    }
    
    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Delete uploaded file
     */
    public function delete(string $filename): bool
    {
        $filepath = $this->config['upload_path'] . '/' . $filename;
        
        if (file_exists($filepath)) {
            unlink($filepath);
            
            // Delete thumbnail if exists
            $pathInfo = pathinfo($filepath);
            $thumbPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];
            if (file_exists($thumbPath)) {
                unlink($thumbPath);
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Get last error
     */
    public function getError(): ?string
    {
        return end($this->errors) ?: null;
    }
}
