<?php

namespace Modules\CMS\Models;

use Core\ORM\Model;
use App\Models\User;

class Media extends Model
{
    protected string $table = 'cms_media';
    
    protected array $fillable = [
        'title', 'filename', 'filepath', 'mime_type', 'file_extension',
        'file_size', 'width', 'height', 'alt_text', 'caption',
        'description', 'uploaded_by', 'folder', 'download_count'
    ];
    
    protected array $casts = [
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'download_count' => 'integer'
    ];
    
    /**
     * Get the uploader
     */
    public function uploader(): ?User
    {
        if (!$this->uploaded_by) {
            return null;
        }
        return User::find($this->uploaded_by);
    }
    
    /**
     * Check if media is an image
     */
    public function isImage(): bool
    {
        return strpos($this->mime_type, 'image/') === 0;
    }
    
    /**
     * Check if media is a video
     */
    public function isVideo(): bool
    {
        return strpos($this->mime_type, 'video/') === 0;
    }
    
    /**
     * Check if media is a document
     */
    public function isDocument(): bool
    {
        $docTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument'];
        foreach ($docTypes as $type) {
            if (strpos($this->mime_type, $type) === 0) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get file size in human readable format
     */
    public function getReadableSize(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Get full URL
     */
    public function getUrl(): string
    {
        return '/storage/' . ltrim($this->filepath, '/');
    }
    
    /**
     * Get thumbnail URL (for images)
     */
    public function getThumbnailUrl(int $width = 150, int $height = 150): string
    {
        if (!$this->isImage()) {
            return $this->getUrl();
        }
        return '/media/thumbnail/' . $this->id . "?w={$width}&h={$height}";
    }
    
    /**
     * Increment download count
     */
    public function incrementDownloads(): void
    {
        $this->download_count = ($this->download_count ?? 0) + 1;
        $this->save();
    }
    
    /**
     * Delete file from storage
     */
    public function deleteFile(): bool
    {
        $fullPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/storage/' . ltrim($this->filepath, '/');
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return true;
    }
}
