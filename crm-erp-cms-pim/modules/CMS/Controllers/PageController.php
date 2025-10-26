<?php

namespace Modules\CMS\Controllers;

use Core\Request;
use Core\Response;
use Modules\CMS\Services\ContentService;
use Core\Validation\Validator;

/**
 * Page Controller
 */
class PageController
{
    private ContentService $contentService;
    
    public function __construct()
    {
        $this->contentService = new ContentService();
    }
    
    /**
     * List pages
     */
    public function index(Request $request): Response
    {
        $pages = \Modules\CMS\Models\Page::all();
        
        if ($request->expectsJson()) {
            return Response::json(['pages' => $pages]);
        }
        
        return view('cms/pages/index', ['pages' => $pages]);
    }
    
    /**
     * Create page
     */
    public function store(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:200',
            'slug' => 'required|unique:cms_pages',
            'content' => 'required',
            'status' => 'required|in:draft,published',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $page = $this->contentService->createPage($validator->validated(), auth()->id());
            return Response::json(['page' => $page, 'message' => 'Page created'], 201);
        } catch (\Exception $e) {
            logger()->error('Page creation failed', ['error' => $e->getMessage()]);
            return Response::json(['error' => 'Failed to create page'], 500);
        }
    }
    
    /**
     * Update page
     */
    public function update(Request $request, int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'title' => 'max:200',
            'content' => 'string',
            'status' => 'in:draft,published',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $page = $this->contentService->updatePage($id, $validator->validated(), auth()->id());
            return Response::json(['page' => $page, 'message' => 'Page updated']);
        } catch (\Exception $e) {
            logger()->error('Page update failed', ['error' => $e->getMessage()]);
            return Response::json(['error' => 'Failed to update page'], 500);
        }
    }
    
    /**
     * Publish page
     */
    public function publish(Request $request, int $id): Response
    {
        try {
            $page = $this->contentService->publishPage($id, auth()->id());
            return Response::json(['page' => $page, 'message' => 'Page published']);
        } catch (\Exception $e) {
            return Response::json(['error' => 'Failed to publish page'], 500);
        }
    }
    
    /**
     * Get page by slug (public)
     */
    public function show(Request $request, string $slug): Response
    {
        $page = $this->contentService->getPageBySlug($slug);
        
        if (!$page) {
            return Response::json(['error' => 'Page not found'], 404);
        }
        
        if ($request->expectsJson()) {
            return Response::json(['page' => $page]);
        }
        
        return view('cms/pages/show', ['page' => $page]);
    }
}
