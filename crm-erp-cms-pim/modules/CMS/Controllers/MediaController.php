<?php

namespace Modules\CMS\Controllers;

use Core\Request;
use Core\Response;
use Modules\CMS\Models\Media;
use Core\File\FileUploader;
use Core\Validation\Validator;

/**
 * Media Controller
 * 
 * Handles media library management
 */
class MediaController
{
    public function index(Request $request): Response
    {
        $type = $request->get('type');
        
        $query = \Core\Database\DB::table('cms_media');
        
        if ($type) {
            $query->where('mime_type', 'LIKE', "{$type}%");
        }
        
        $media = $query->orderBy('created_at', 'DESC')->get();
        
        if ($request->expectsJson()) {
            return Response::json(['media' => $media]);
        }
        
        return view('cms/media/index', ['media' => $media, 'type' => $type]);
    }
    
    public function upload(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'title' => 'max:200',
            'alt_text' => 'max:200',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $uploader = new FileUploader();
            $file = $uploader->upload('file', 'uploads/media');
            
            $media = Media::create([
                'title' => $validator->validated()['title'] ?? $file['original_name'],
                'filename' => $file['filename'],
                'filepath' => $file['path'],
                'mime_type' => $file['mime_type'],
                'filesize' => $file['size'],
                'alt_text' => $validator->validated()['alt_text'] ?? '',
                'uploaded_by' => auth()->id(),
            ]);
            
            return Response::json(['media' => $media, 'message' => 'File uploaded successfully']);
        } catch (\Exception $e) {
            logger()->error('Media upload failed', ['error' => $e->getMessage()]);
            return Response::json(['error' => 'Failed to upload file'], 500);
        }
    }
    
    public function destroy(Request $request, int $id): Response
    {
        try {
            $media = Media::findOrFail($id);
            
            // Delete file from storage
            if (file_exists($media['filepath'])) {
                unlink($media['filepath']);
            }
            
            $media->delete();
            
            return Response::json(['message' => 'Media deleted successfully']);
        } catch (\Exception $e) {
            logger()->error('Media deletion failed', ['error' => $e->getMessage(), 'media_id' => $id]);
            return Response::json(['error' => 'Failed to delete media'], 500);
        }
    }
}
