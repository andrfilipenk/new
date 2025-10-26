<?php

namespace Modules\PIM\Models;

use Core\ORM\Model;

class PimCategory extends Model
{
    protected string $table = 'pim_categories';
    
    protected array $fillable = [
        'name', 'slug', 'description', 'parent_id', 'level', 'path',
        'order', 'image', 'meta_title', 'meta_description', 'is_active', 'product_count'
    ];
    
    protected array $casts = [
        'level' => 'integer',
        'order' => 'integer',
        'product_count' => 'integer',
        'is_active' => 'boolean'
    ];
    
    /**
     * Get the parent category
     */
    public function parent(): ?self
    {
        if (!$this->parent_id) {
            return null;
        }
        return self::find($this->parent_id);
    }
    
    /**
     * Get child categories
     */
    public function children(): array
    {
        return self::where('parent_id', $this->id)
            ->where('is_active', true)
            ->orderBy('order', 'ASC')
            ->get();
    }
    
    /**
     * Get all ancestor categories
     */
    public function ancestors(): array
    {
        $ancestors = [];
        $current = $this;
        
        while ($current->parent_id) {
            $parent = $current->parent();
            if (!$parent) break;
            array_unshift($ancestors, $parent);
            $current = $parent;
        }
        
        return $ancestors;
    }
    
    /**
     * Get products in this category
     */
    public function products(): array
    {
        $pivots = Model::query()
            ->table('pim_product_categories')
            ->where('category_id', $this->id)
            ->get();
        
        $products = [];
        foreach ($pivots as $pivot) {
            $product = Product::find($pivot['product_id']);
            if ($product && $product->status === 'active') {
                $products[] = $product;
            }
        }
        return $products;
    }
    
    /**
     * Update product count
     */
    public function updateProductCount(): void
    {
        $count = Model::query()
            ->table('pim_product_categories')
            ->where('category_id', $this->id)
            ->count();
        
        $this->product_count = $count;
        $this->save();
    }
    
    /**
     * Build category path
     */
    public function buildPath(): string
    {
        $ancestors = $this->ancestors();
        $path = '/';
        
        foreach ($ancestors as $ancestor) {
            $path .= $ancestor->slug . '/';
        }
        $path .= $this->slug;
        
        return $path;
    }
    
    /**
     * Update path and level
     */
    public function updateHierarchy(): void
    {
        $ancestors = $this->ancestors();
        $this->level = count($ancestors);
        $this->path = $this->buildPath();
        $this->save();
        
        // Update children recursively
        foreach ($this->children() as $child) {
            $child->updateHierarchy();
        }
    }
    
    /**
     * Get full URL
     */
    public function getUrl(): string
    {
        return '/categories' . $this->path;
    }
}
