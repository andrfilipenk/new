<?php

namespace Modules\CMS\Services;

use Modules\CMS\Models\Page;
use Modules\CMS\Models\Revision;
use App\Services\AuditService;
use Core\Cache\CacheManager;

/**
 * Content Management Service
 */
class ContentService
{
    private CacheManager $cache;
    
    public function __construct()
    {
        $this->cache = CacheManager::getInstance();
    }
    
    /**
     * Create page
     */
    public function createPage(array $data, ?int $userId = null): Page
    {
        $page = Page::create($data);
        
        // Create initial revision
        if ($userId) {
            $this->createRevision($page, $userId, 'created');
        }
        
        AuditService::logCreate('Page', $page->id, $data, $userId);
        
        // Clear page cache
        $this->clearPageCache($page->slug);
        
        return $page;
    }
    
    /**
     * Update page
     */
    public function updatePage(int $pageId, array $data, ?int $userId = null): Page
    {
        $page = Page::findOrFail($pageId);
        $oldData = $page->toArray();
        
        $page->update($data);
        
        // Create revision on update
        if ($userId) {
            $this->createRevision($page, $userId, 'updated');
        }
        
        AuditService::logUpdate('Page', $pageId, $oldData, $data, $userId);
        
        // Clear cache
        $this->clearPageCache($page->slug);
        if (isset($oldData['slug']) && $oldData['slug'] !== $page->slug) {
            $this->clearPageCache($oldData['slug']);
        }
        
        return $page;
    }
    
    /**
     * Publish page
     */
    public function publishPage(int $pageId, ?int $userId = null): Page
    {
        $page = Page::findOrFail($pageId);
        
        $page->status = 'published';
        $page->published_at = date('Y-m-d H:i:s');
        $page->save();
        
        if ($userId) {
            $this->createRevision($page, $userId, 'published');
        }
        
        $this->clearPageCache($page->slug);
        
        return $page;
    }
    
    /**
     * Unpublish page
     */
    public function unpublishPage(int $pageId, ?int $userId = null): Page
    {
        $page = Page::findOrFail($pageId);
        
        $page->status = 'draft';
        $page->save();
        
        if ($userId) {
            $this->createRevision($page, $userId, 'unpublished');
        }
        
        $this->clearPageCache($page->slug);
        
        return $page;
    }
    
    /**
     * Get page by slug with caching
     */
    public function getPageBySlug(string $slug): ?Page
    {
        $cacheKey = "page:{$slug}";
        
        $pageData = $this->cache->get($cacheKey);
        
        if ($pageData) {
            return Page::hydrate($pageData);
        }
        
        $page = Page::query()->where('slug', '=', $slug)->first();
        
        if ($page) {
            $pageArray = \Core\Database\DB::table('cms_pages')
                ->where('slug', '=', $slug)
                ->first();
            $this->cache->put($cacheKey, $pageArray, 3600);
            return Page::hydrate($pageArray);
        }
        
        return null;
    }
    
    /**
     * Create revision
     */
    private function createRevision(Page $page, int $userId, string $type = 'auto'): Revision
    {
        return Revision::create([
            'revisionable_type' => 'Page',
            'revisionable_id' => $page->id,
            'content' => json_encode($page->toArray()),
            'user_id' => $userId,
            'type' => $type,
        ]);
    }
    
    /**
     * Restore from revision
     */
    public function restoreRevision(int $revisionId, ?int $userId = null): Page
    {
        $revision = Revision::findOrFail($revisionId);
        $data = json_decode($revision->content, true);
        
        $page = Page::findOrFail($revision->revisionable_id);
        $page->update($data);
        
        if ($userId) {
            $this->createRevision($page, $userId, 'restored');
        }
        
        $this->clearPageCache($page->slug);
        
        return $page;
    }
    
    /**
     * Clear page cache
     */
    private function clearPageCache(string $slug): void
    {
        $this->cache->forget("page:{$slug}");
    }
    
    /**
     * Build page tree for hierarchical display
     */
    public function buildPageTree(?int $parentId = null): array
    {
        $pages = Page::query()
            ->where('parent_id', $parentId === null ? 'IS NULL' : '=', $parentId)
            ->orderBy('order', 'ASC')
            ->get();
        
        $tree = [];
        
        foreach ($pages as $pageData) {
            $page = Page::hydrate($pageData);
            $tree[] = [
                'page' => $page,
                'children' => $this->buildPageTree($page->id),
            ];
        }
        
        return $tree;
    }
}
