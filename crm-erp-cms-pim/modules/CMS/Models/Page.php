<?php

namespace Modules\CMS\Models;

use Core\ORM\Model;
use App\Models\User;

class Page extends Model
{
    protected string $table = 'cms_pages';
    
    protected array $fillable = [
        'title', 'slug', 'content', 'excerpt', 'template', 'status',
        'visibility', 'password', 'parent_id', 'author_id', 'order',
        'meta_title', 'meta_description', 'meta_keywords', 'featured_image',
        'is_homepage', 'show_in_menu', 'view_count', 'published_at'
    ];
    
    protected array $casts = [
        'is_homepage' => 'boolean',
        'show_in_menu' => 'boolean',
        'view_count' => 'integer',
        'order' => 'integer',
        'published_at' => 'datetime'
    ];
    
    protected bool $useSoftDeletes = true;
    
    /**
     * Get the author of the page
     */
    public function author(): ?User
    {
        if (!$this->author_id) {
            return null;
        }
        return User::find($this->author_id);
    }
    
    /**
     * Get the parent page
     */
    public function parent(): ?self
    {
        if (!$this->parent_id) {
            return null;
        }
        return self::find($this->parent_id);
    }
    
    /**
     * Get child pages
     */
    public function children(): array
    {
        return self::where('parent_id', $this->id)->get();
    }
    
    /**
     * Check if page is published
     */
    public function isPublished(): bool
    {
        return $this->status === 'published' && 
               (!$this->published_at || strtotime($this->published_at) <= time());
    }
    
    /**
     * Check if page is draft
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }
    
    /**
     * Check if page requires password
     */
    public function requiresPassword(): bool
    {
        return $this->visibility === 'password' && !empty($this->password);
    }
    
    /**
     * Increment view count
     */
    public function incrementViews(): void
    {
        $this->view_count = ($this->view_count ?? 0) + 1;
        $this->save();
    }
    
    /**
     * Get full URL
     */
    public function getUrl(): string
    {
        if ($this->is_homepage) {
            return '/';
        }
        return '/pages/' . $this->slug;
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
    
    /**
     * Create a revision
     */
    public function createRevision(int $userId, string $type = 'auto'): void
    {
        Revision::create([
            'revisionable_type' => 'Page',
            'revisionable_id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'metadata' => json_encode([
                'template' => $this->template,
                'status' => $this->status,
                'visibility' => $this->visibility,
                'meta_title' => $this->meta_title,
                'meta_description' => $this->meta_description
            ]),
            'user_id' => $userId,
            'revision_type' => $type
        ]);
    }
    
    /**
     * Get revisions
     */
    public function revisions(): array
    {
        return Revision::where('revisionable_type', 'Page')
            ->where('revisionable_id', $this->id)
            ->orderBy('created_at', 'DESC')
            ->get();
    }
}
