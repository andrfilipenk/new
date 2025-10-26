<?php

namespace Modules\CMS\Services;

use Modules\CMS\Models\Menu;
use Modules\CMS\Models\MenuItem;
use App\Services\AuditService;
use Core\Cache\CacheManager;

/**
 * Menu Builder Service
 */
class MenuService
{
    private CacheManager $cache;
    
    public function __construct()
    {
        $this->cache = CacheManager::getInstance();
    }
    
    /**
     * Create menu
     */
    public function createMenu(array $data, ?int $userId = null): Menu
    {
        $menu = Menu::create($data);
        
        AuditService::logCreate('Menu', $menu->id, $data, $userId);
        
        $this->clearMenuCache($menu->slug);
        
        return $menu;
    }
    
    /**
     * Update menu
     */
    public function updateMenu(int $menuId, array $data, ?int $userId = null): Menu
    {
        $menu = Menu::findOrFail($menuId);
        $oldData = $menu->toArray();
        
        $menu->update($data);
        
        AuditService::logUpdate('Menu', $menuId, $oldData, $data, $userId);
        
        $this->clearMenuCache($menu->slug);
        
        return $menu;
    }
    
    /**
     * Add menu item
     */
    public function addMenuItem(int $menuId, array $data, ?int $userId = null): MenuItem
    {
        $data['menu_id'] = $menuId;
        
        // Set order if not provided
        if (!isset($data['order'])) {
            $maxOrder = \Core\Database\DB::table('cms_menu_items')
                ->where('menu_id', '=', $menuId)
                ->where('parent_id', $data['parent_id'] ?? null ? '=' : 'IS NULL', $data['parent_id'] ?? null)
                ->max('order');
            $data['order'] = ($maxOrder ?? 0) + 1;
        }
        
        $menuItem = MenuItem::create($data);
        
        AuditService::logCreate('MenuItem', $menuItem->id, $data, $userId);
        
        $menu = Menu::findOrFail($menuId);
        $this->clearMenuCache($menu->slug);
        
        return $menuItem;
    }
    
    /**
     * Update menu item
     */
    public function updateMenuItem(int $itemId, array $data, ?int $userId = null): MenuItem
    {
        $menuItem = MenuItem::findOrFail($itemId);
        $oldData = $menuItem->toArray();
        
        $menuItem->update($data);
        
        AuditService::logUpdate('MenuItem', $itemId, $oldData, $data, $userId);
        
        $menu = Menu::findOrFail($menuItem->menu_id);
        $this->clearMenuCache($menu->slug);
        
        return $menuItem;
    }
    
    /**
     * Delete menu item
     */
    public function deleteMenuItem(int $itemId, ?int $userId = null): bool
    {
        $menuItem = MenuItem::findOrFail($itemId);
        
        // Delete children first
        $children = MenuItem::query()
            ->where('parent_id', '=', $itemId)
            ->get();
        
        foreach ($children as $child) {
            $childItem = MenuItem::hydrate($child);
            $this->deleteMenuItem($childItem->id, $userId);
        }
        
        AuditService::logDelete('MenuItem', $itemId, $menuItem->toArray(), $userId);
        
        $menu = Menu::findOrFail($menuItem->menu_id);
        $this->clearMenuCache($menu->slug);
        
        return $menuItem->delete();
    }
    
    /**
     * Reorder menu items
     */
    public function reorderItems(int $menuId, array $order, ?int $userId = null): void
    {
        foreach ($order as $position => $itemId) {
            \Core\Database\DB::table('cms_menu_items')
                ->where('id', '=', $itemId)
                ->update(['order' => $position + 1]);
        }
        
        $menu = Menu::findOrFail($menuId);
        $this->clearMenuCache($menu->slug);
    }
    
    /**
     * Get menu by slug with caching
     */
    public function getMenuBySlug(string $slug): ?Menu
    {
        $cacheKey = "menu:{$slug}";
        
        $menuData = $this->cache->get($cacheKey);
        
        if ($menuData) {
            return Menu::hydrate($menuData);
        }
        
        $menuArray = \Core\Database\DB::table('cms_menus')
            ->where('slug', '=', $slug)
            ->first();
        
        if ($menuArray) {
            $this->cache->put($cacheKey, $menuArray, 3600);
            return Menu::hydrate($menuArray);
        }
        
        return null;
    }
    
    /**
     * Build menu tree
     */
    public function buildMenuTree(int $menuId, ?int $parentId = null): array
    {
        $items = MenuItem::query()
            ->where('menu_id', '=', $menuId)
            ->where('parent_id', $parentId === null ? 'IS NULL' : '=', $parentId)
            ->orderBy('order', 'ASC')
            ->get();
        
        $tree = [];
        
        foreach ($items as $itemData) {
            $item = MenuItem::hydrate($itemData);
            $tree[] = [
                'item' => $item,
                'children' => $this->buildMenuTree($menuId, $item->id),
            ];
        }
        
        return $tree;
    }
    
    /**
     * Render menu as HTML
     */
    public function renderMenu(string $slug, string $cssClass = 'nav'): string
    {
        $menu = $this->getMenuBySlug($slug);
        
        if (!$menu) {
            return '';
        }
        
        $tree = $this->buildMenuTree($menu->id);
        
        return $this->renderMenuItems($tree, $cssClass);
    }
    
    /**
     * Render menu items recursively
     */
    private function renderMenuItems(array $items, string $cssClass, int $depth = 0): string
    {
        if (empty($items)) {
            return '';
        }
        
        $html = "<ul class=\"{$cssClass}\">";
        
        foreach ($items as $node) {
            $item = $node['item'];
            $hasChildren = !empty($node['children']);
            
            $html .= '<li class="nav-item' . ($hasChildren ? ' dropdown' : '') . '">';
            $html .= '<a href="' . htmlspecialchars($item->url) . '" class="nav-link"';
            
            if ($item->target) {
                $html .= ' target="' . htmlspecialchars($item->target) . '"';
            }
            
            $html .= '>' . htmlspecialchars($item->title) . '</a>';
            
            if ($hasChildren) {
                $html .= $this->renderMenuItems($node['children'], 'dropdown-menu', $depth + 1);
            }
            
            $html .= '</li>';
        }
        
        $html .= '</ul>';
        
        return $html;
    }
    
    /**
     * Clear menu cache
     */
    private function clearMenuCache(string $slug): void
    {
        $this->cache->forget("menu:{$slug}");
    }
}
