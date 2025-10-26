<?php

namespace Modules\CMS\Models;

use Core\ORM\Model;

class MenuItem extends Model
{
    protected string $table = 'cms_menu_items';
    
    protected array $fillable = [
        'menu_id', 'parent_id', 'title', 'url', 'type', 'target_id',
        'target', 'css_class', 'icon', 'order', 'is_active'
    ];
    
    protected array $casts = [
        'order' => 'integer',
        'target_id' => 'integer',
        'is_active' => 'boolean'
    ];
    
    /**
     * Get the parent menu
     */
    public function menu(): ?Menu
    {
        if (!$this->menu_id) {
            return null;
        }
        return Menu::find($this->menu_id);
    }
    
    /**
     * Get parent item
     */
    public function parent(): ?self
    {
        if (!$this->parent_id) {
            return null;
        }
        return self::find($this->parent_id);
    }
    
    /**
     * Get child items
     */
    public function children(): array
    {
        return self::where('parent_id', $this->id)
            ->where('is_active', true)
            ->orderBy('order', 'ASC')
            ->get();
    }
    
    /**
     * Get the URL for this menu item
     */
    public function getUrl(): string
    {
        if ($this->type === 'custom' || $this->type === 'external') {
            return $this->url;
        }
        
        if ($this->type === 'page' && $this->target_id) {
            $page = Page::find($this->target_id);
            return $page ? $page->getUrl() : '#';
        }
        
        if ($this->type === 'post' && $this->target_id) {
            $post = Post::find($this->target_id);
            return $post ? $post->getUrl() : '#';
        }
        
        if ($this->type === 'category' && $this->target_id) {
            $category = Category::find($this->target_id);
            return $category ? $category->getUrl() : '#';
        }
        
        return $this->url ?? '#';
    }
    
    /**
     * Check if item has children
     */
    public function hasChildren(): bool
    {
        return !empty($this->children());
    }
}
