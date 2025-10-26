<?php

namespace Modules\CMS\Models;

use Core\ORM\Model;

class Menu extends Model
{
    protected string $table = 'cms_menus';
    
    protected array $fillable = [
        'name', 'slug', 'location', 'description', 'is_active'
    ];
    
    protected array $casts = [
        'is_active' => 'boolean'
    ];
    
    /**
     * Get menu items
     */
    public function items(): array
    {
        return MenuItem::where('menu_id', $this->id)
            ->where('parent_id', null)
            ->orderBy('order', 'ASC')
            ->get();
    }
    
    /**
     * Get all items (including nested)
     */
    public function allItems(): array
    {
        return MenuItem::where('menu_id', $this->id)
            ->orderBy('order', 'ASC')
            ->get();
    }
    
    /**
     * Build hierarchical menu structure
     */
    public function getStructure(): array
    {
        $items = $this->items();
        return $this->buildTree($items);
    }
    
    /**
     * Build tree structure recursively
     */
    private function buildTree(array $items): array
    {
        $tree = [];
        foreach ($items as $item) {
            $node = $item->toArray();
            $children = $item->children();
            if (!empty($children)) {
                $node['children'] = $this->buildTree($children);
            }
            $tree[] = $node;
        }
        return $tree;
    }
}
