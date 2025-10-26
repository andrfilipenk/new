<?php

namespace Modules\CMS\Services;

use Modules\CMS\Models\Media;
use Core\File\FileUploader;
use App\Services\AuditService;

/**
 * Media Management Service
 */
class MediaService
{
    private FileUploader $uploader;
    
    public function __construct()
    {
        $this->uploader = new FileUploader();
    }
    
    /**
     * Upload media file
     */
    public function upload(array $file, ?array $metadata = null, ?int $userId = null): Media
    {
        $uploadResult = $this->uploader->upload($file);
        
        if (!$uploadResult) {
            throw new \RuntimeException('File upload failed');
        }
        
        $media = Media::create([
            'title' => $metadata['title'] ?? $file['name'],
            'file_name' => $uploadResult['filename'],
            'file_path' => $uploadResult['path'],
            'file_size' => $uploadResult['size'],
            'mime_type' => $uploadResult['mime_type'],
            'file_type' => $this->determineFileType($uploadResult['mime_type']),
            'alt_text' => $metadata['alt_text'] ?? null,
            'caption' => $metadata['caption'] ?? null,
            'uploaded_by' => $userId,
        ]);
        
        AuditService::logCreate('Media', $media->id, $media->toArray(), $userId);
        
        return $media;
    }
    
    /**
     * Upload multiple files
     */
    public function uploadMultiple(array $files, ?int $userId = null): array
    {
        $uploadedMedia = [];
        
        foreach ($files as $file) {
            try {
                $uploadedMedia[] = $this->upload($file, null, $userId);
            } catch (\Exception $e) {
                // Log error but continue with other files
                logger()->error('Media upload failed', ['file' => $file['name'], 'error' => $e->getMessage()]);
            }
        }
        
        return $uploadedMedia;
    }
    
    /**
     * Update media metadata
     */
    public function updateMetadata(int $mediaId, array $metadata, ?int $userId = null): Media
    {
        $media = Media::findOrFail($mediaId);
        $oldData = $media->toArray();
        
        $media->update($metadata);
        
        AuditService::logUpdate('Media', $mediaId, $oldData, $metadata, $userId);
        
        return $media;
    }
    
    /**
     * Delete media
     */
    public function delete(int $mediaId, ?int $userId = null): bool
    {
        $media = Media::findOrFail($mediaId);
        
        // Delete physical file
        $filePath = storage_path('uploads/' . $media->file_path);
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Delete thumbnail if exists
        $thumbPath = storage_path('uploads/thumbnails/' . $media->file_path);
        if (file_exists($thumbPath)) {
            unlink($thumbPath);
        }
        
        AuditService::logDelete('Media', $mediaId, $media->toArray(), $userId);
        
        return $media->delete();
    }
    
    /**
     * Get media by type
     */
    public function getByType(string $type): array
    {
        return Media::query()
            ->where('file_type', '=', $type)
            ->orderBy('created_at', 'DESC')
            ->get();
    }
    
    /**
     * Search media
     */
    public function search(string $query): array
    {
        return Media::query()
            ->where('title', 'LIKE', "%{$query}%")
            ->orWhere('file_name', 'LIKE', "%{$query}%")
            ->orWhere('alt_text', 'LIKE', "%{$query}%")
            ->orderBy('created_at', 'DESC')
            ->get();
    }
    
    /**
     * Determine file type from MIME type
     */
    private function determineFileType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'video';
        } elseif (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        } elseif (in_array($mimeType, ['application/pdf'])) {
            return 'document';
        }
        
        return 'other';
    }
    
    /**
     * Get storage statistics
     */
    public function getStorageStats(): array
    {
        $media = Media::all();
        
        $stats = [
            'total_files' => count($media),
            'total_size' => 0,
            'by_type' => [],
        ];
        
        foreach ($media as $item) {
            $mediaObj = Media::hydrate($item);
            $stats['total_size'] += $mediaObj->file_size;
            
            $type = $mediaObj->file_type;
            if (!isset($stats['by_type'][$type])) {
                $stats['by_type'][$type] = ['count' => 0, 'size' => 0];
            }
            $stats['by_type'][$type]['count']++;
            $stats['by_type'][$type]['size'] += $mediaObj->file_size;
        }
        
        return $stats;
    }
}
