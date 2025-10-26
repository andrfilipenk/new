<?php

namespace Modules\CMS\Models;

use Core\ORM\Model;

class Category extends Model
{
    protected string $table = 'cms_categories';
    
    protected array $fillable = [
        'name', 'slug', 'description', 'parent_id', 'order',
        'meta_title', 'meta_description', 'image', 'post_count'
    ];
    
    protected array $casts = [
        'order' => 'integer',
        'post_count' => 'integer'
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
            ->orderBy('order', 'ASC')
            ->get();
    }
    
    /**
     * Get posts in this category
     */
    public function posts(): array
    {
        $pivots = Model::query()
            ->table('cms_post_category')
            ->where('category_id', $this->id)
            ->get();
        
        $posts = [];
        foreach ($pivots as $pivot) {
            $post = Post::find($pivot['post_id']);
            if ($post && $post->isPublished()) {
                $posts[] = $post;
            }
        }
        return $posts;
    }
    
    /**
     * Update post count
     */
    public function updatePostCount(): void
    {
        $count = Model::query()
            ->table('cms_post_category')
            ->where('category_id', $this->id)
            ->count();
        
        $this->post_count = $count;
        $this->save();
    }
    
    /**
     * Get full URL
     */
    public function getUrl(): string
    {
        return '/categories/' . $this->slug;
    }
    
    /**
     * Get breadcrumb trail
     */
    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [$this];
        $current = $this;
        
        while ($current->parent_id) {
            $parent = $current->parent();
            if (!$parent) break;
            array_unshift($breadcrumbs, $parent);
            $current = $parent;
        }
        
        return $breadcrumbs;
    }
}
